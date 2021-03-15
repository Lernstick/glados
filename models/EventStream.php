<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\EventItem;

/**
 * This is the model class for event streams.
 *
 * @property integer $id
 * @property string $uuid string unique id identifying the event stream
 * @property double $stopped_at timestamp in seconds with microseconds when the event stream has stopped.
 * @property integer $started_at timestamp in seconds when the event stream has started.
 * @property string $listenEvents array the names of all the event that the streamer should listen on, comma separated.
 */
class EventStream extends EventItem
{

    /**
     * @event Event an event that is triggered when the stream has started in [[start()]].
     */
    const EVENT_STREAM_STARTED = 'streamStarted';
    /**
     * @event Event an event that is triggered when the stream has stopped in [[stop()]].
     */    
    const EVENT_STREAM_STOPPED = 'streamStopped';
    /**
     * @event Event an event that is triggered when the stream has resumed a previous instance in [[start()]].
     */
    const EVENT_STREAM_RESUMED = 'streamResumed';
    /**
     * @event Event an event that is triggered when the stream was aborted by the user.
     */
    const EVENT_STREAM_ABORTED = 'streamAborted';
    /**
     * @event Event an event that is triggered when the stream could not be started.
     */
    const EVENT_STREAM_FAILED = 'streamFailed';
    /**
     * @var integer defines the time in seconds how long the stream should run.
     */
    public $timeLimit = 120;
    /**
     * @var integer maximal time in seconds after which the database should be polled/queried even if no inotify
     * event has triggered. Notice that the read timeout set in lernstick-exam-agent should be a multiple of this
     * (say 3 times this but NOT less)
     */
    public $pollTime = 5;
    /**
     * @var array Array containing all events generated by [[onEvent()]]. Each item is of type app\models\EventItem.
     */
    public $events = [];
    /**
     * @var integer number of events sent.
     */
    public $sentEvents;
    /**
     * @var array Array holding all id's of events that are sent during this invocation
     */
    public $sentIds = [];
    /**
     * @var resource the file descriptor resource holding the inotify instance.
     */
    private $_fd;
    /**
     * @var float timestamp in seconds with microseconds when the stream has started.
     */
    private $_startTime;
    /**
     * @var array An array holding all unique (inotify instance wide) watch descriptors for files watched
     * by the event [[IN_CLOSE_WRITE]]. For example, this array could look like:
     * [
     *    'ticket/1234' => watch descriptor
     * ]
     */
    private $_fwd = [];
    /**
     * @var array An array holding all unique (inotify instance wide) watch descriptors for directories watched
     * the event [[IN_CREATE]]. It looks similar to [[_fwd]]
     */
    private $_dwd = [];
    /**
     * @var array the names of all the event that the streamer should listen on.
     */
    private $_listenEvents = [];
    /**
     * @var float|null timestamp in seconds with microseconds when the stream has resumed listening
     * to inotify events.
     */
    private $_resumeListeningTime = null;
    /**
     * @var float|null timestamp in seconds with microseconds when the stream has stopped listening
     * to inotify events. This time also holds if a stream has reestablished.
     */
    private $_stopListeningTime = null;
    /**
     * @var float|null timestamp in seconds with microseconds of the most recently sent event.
     */
    private $_mostRecentEventTime = null;


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'event_stream';
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->_startTime = microtime(true);
    }

    /**
     * Initializes the inotify instance and its file descriptor, the stream is set to blocking mode
     * that we can listen on it later and wait until activity.
     *
     * Sets up the stream with the time limit and the needed headers. If the stream is resumed (the
     * time limit was exceeded and the client browser restarted the SSE stream) the function will
     * determine when the previous stream stopped and raises the event [[EVENT_STREAM_RESUMED]] and
     * sets [[_resumeListeningTime]] or, if not resumed, raises the event [[EVENT_STREAM_STARTED]].
     *
     * @return boolean success or failure
     */
    public function start()
    {
        ob_end_flush();
        ob_start();

        $this->_fd = inotify_init();
        stream_set_blocking($this->_fd, true);

        set_time_limit($this->timeLimit + 1);
        ignore_user_abort(true);
        clearstatcache();

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header("Connection: keep-alive");

        if (Yii::$app->mutex->acquire($this->uuid)) {
            if (isset($this->stopped_at)) {
                // set the stopListeningTime from the last stream
                $this->_stopListeningTime = $this->stopped_at;
                $this->trigger(self::EVENT_STREAM_RESUMED);
            } else {
                $this->trigger(self::EVENT_STREAM_STARTED);
            }
            return true;
        } else {
            $this->trigger(self::EVENT_STREAM_FAILED);
            return false;
        }
    }

    /**
     * Stops the event stream and raises the [[EVENT_STREAM_STOPPED]] event. The db record is saved that
     * in the next invocation the stream can be resumed by the [[uuid]].
     *
     * @return void
     */
    public function stop()
    {
        $this->stopped_at = $this->_mostRecentEventTime != null
            ? $this->_mostRecentEventTime
            : $this->_stopListeningTime;
        $this->save();
        Yii::$app->mutex->release($this->uuid);
        $this->trigger(self::EVENT_STREAM_STOPPED);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uuid', 'listenEvents'], 'required'],
            [['stopped_at'], 'number'],
            [['uuid'], 'string', 'max' => 36],
        ];
    }

    /**
     * Getter for the isActive flag.
     *
     * @return boolean whether the stream is active or not
     */
    public function getIsActive()
    {
        $retval = Yii::$app->mutex->acquire($this->uuid);
        if ($retval) {
            Yii::$app->mutex->release($this->uuid);
        }
        return !$retval;
    }

    /**
     * @return float amount of seconds with microseconds to stream from now on.
     * For example: 9.345 means 9 seconds and 345 ms.
     * 
     */
    public function calcTimeout()
    {
        $sec =  $this->timeLimit - (microtime(true) - $this->_startTime);
        return $sec > $this->pollTime ? $this->pollTime : $sec;
    }

    /**
     * @return array Array with two elements; the first element contains the amount of seconds, the second 
     * element contains the amount of microseconds. Those two values together give the exact time from 
     * [[calcTimeout()]]. This is for the timeout value of the [[stream_select()]] function and is only 
     * needed there.
     */
    private function getTimeoutForSelect()
    {
        $exact = $this->calcTimeout();
        $seconds = floor($exact);
        $microseconds = floor(round(($exact-$seconds)*1000000));
        return [
            $seconds,
            $microseconds,
        ];
    }


    /**
     * The [[_listenEvents]] array is set from values from the database and  if there is an event defined to
     * listen on multiple events, then this is expanded. For example the event "daemon/*" is expanded to all
     * files in the directory daemon/.
     * @return void
     */
    private function setupWatches(){
        // Repopulates this active record with the latest data from the database.
        $this->refresh();

        $this->_listenEvents = explode(',', $this->listenEvents);

        # remove the group name if present
        $this->_listenEvents = preg_replace('/^.+\:/', '', $this->_listenEvents);

        // Also listen to the bump event "event/{uuid}". This is the event describing that something about
        // the current stream has changed (for example new events to listen to or old events to stop listen
        // to).
        $this->_listenEvents[] = 'event/' . $this->uuid;

        foreach ($this->_listenEvents as $event) {
            if (basename($event) == "*") {
                $files = scandir($this->inotifyDir . '/' . dirname($event));
                foreach ($files as $file) {
                    if (is_file($this->inotifyDir . '/' . dirname($event) . '/' . $file)) {
                        $this->_listenEvents[] = dirname($event) . '/' . $file;
                    }
                }
                $this->_listenEvents[] = dirname($event) . '/ALL';
            }
        }
        $this->_listenEvents = array_unique($this->_listenEvents);
    }

    /**
     * Calls [[setupWachtes()]] and sets up all the needed inotify watches. If the event file exists, 
     * an inotify watch is added for the [[IN_CLOSE_WRITE]] event. If the file does not exist, an event
     * [[IN_CREATE]] is added to the containing directory. If the directory itself does not exist too, an
     * event [[IN_CREATE]] to the directory one level up is set. This goes on until a directory exists,
     * event if it's "/". This function will not set multiple watches at the same directory or file. The
     * watch is only set if there is no watch on that item already.
     * The inotify watch descriptors are stored in the array [[_dwd]] for directories and [[_fwd]] for files.
     *
     * @return integer the number of new inotify watches set.
     */
    private function addWatches(){
        $this->setupWatches();
        $watches = 0;
        foreach ($this->_listenEvents as $event) {
            // the absolute path to the physical file that may or may not reside on the disk
            $eventFile = $this->inotifyDir . '/' . $event;

            if (file_exists($eventFile)) {
                if (!isset($this->_fwd[$event])) {
                    $this->_fwd[$event] = inotify_add_watch($this->_fd, $eventFile, IN_CLOSE_WRITE);
                    $watches++;
                }
            } else {
                $dir = $eventFile;
                // the deepest possible directory that exists will get a inotify watch
                do {
                    $dir = dirname($dir);
                    if (file_exists($dir)) {
                        if (!isset($this->_dwd[$dir])) {
                            $this->_dwd[$dir] = inotify_add_watch($this->_fd, $dir, IN_CREATE);
                            $watches++;
                        }
                        break;
                    }
                } while (dirname($dir) != $dir);
            }
        }
        return $watches;
    }

    /**
     * Removes a watch and removes the element in the corresponding array.
     *
     * @param integer $wd the inotify watch descriptor.
     * @return void
     */
    private function removeWatch($wd){
        @inotify_rm_watch($this->_fd, $wd);
        $event = array_search($wd, $this->_fwd);
        unset($this->_fwd[$event]);
        $event = array_search($wd, $this->_dwd);
        unset($this->_dwd[$event]);
    }

    /**
     * Removes all watches stored in the watch arrays [[_fwd]] and [[_dwd]].
     *
     * @return void
     */
    private function removeWatches(){
        foreach ($this->_fwd as $wd) {
            $this->removeWatch($wd);
        }
        foreach ($this->_dwd as $wd) {
            $this->removeWatch($wd);
        }
    }

    /**
     * This function waits on all streams for an event and generates one or multiple event items.
     * 
     * The first job of this function is to determine if there are events missing. This can happen when the 
     * client browser restarted the event stream. This can take up to ~5 seconds, depending on the connection
     * quality. In that time, events can occur. Also, if an event is generated at the exact time when an event 
     * is processed, another event can occur. All events are stored in the database, which is queried for 
     * such events, called "lost events". So, if [[_stopListeningTime]] is set, we have 
     * to query the db for lost events. If not or the db returns no record, we continue (or start) listening on
     * the streams.
     * 
     * The function then blocks in the call of [[stream_select()]] until an event happend. If that's the case
     * the file now contains the event [[id]] of the db record. This is done by [[EventItem::generate()]]. The
     * db is queried and an [[EventItem]] is appended to the [[events]] array.
     * 
     * @return bool true means there was an event during a watch, false means the timeout was exceeded without
     * an event or the client has disconnected. This is usually the end of this stream instance. If the browser
     * wants to resume the stream, we regain the stream instance via the uuid from the database.
     */
    public function onEvent(){

        while ($this->calcTimeout() > 0) {

            if ($this->idle() !== true) {
                fclose($this->_fd);
                $this->stopped_at = $this->_mostRecentEventTime != null
                    ? $this->_mostRecentEventTime
                    : $this->_stopListeningTime;
                $this->save();
                $this->trigger(self::EVENT_STREAM_ABORTED);
                return false;
            }
            $this->addWatches();

            /* Search for lost events, that occured while:
             * * processing/sending events
             * * or the event stream was reestablished
             */
            if ($this->_stopListeningTime != null) {
                $this->queryEvents(true);
                if (!empty($this->events)) {
                    return true;
                }
            }

            $this->events = [];
            $read = [ $this->_fd ];

            // wait for events
            if ($this->listenToStreams($read) !== 0) {

                // @param resource $socket inotify instance
                foreach ($read as $socket) {

                    // if there are no pending events, continue
                    if (inotify_queue_len($socket) == 0) { continue; }

                    /*
                     * @param array|false $inotifyEvents array of events. Each event is an array with the
                     * following keys:
                     * - wd is a watch descriptor returned by inotify_add_watch()
                     * - mask is a bit mask of events (https://www.php.net/manual/en/inotify.constants.php)
                     * - cookie is a unique id to connect related events (e.g. IN_MOVE_FROM and IN_MOVE_TO)
                     * - name is the name of a file (e.g. if a file was modified in a watched directory)
                     */
                    $inotifyEvents = inotify_read($socket);

                    // if there are no pending events, continue
                    if (!is_array($inotifyEvents)) { continue; }

                    $mask = $inotifyEvents[0]['mask'];
                    $wd = $inotifyEvents[0]['wd'];

                    // File opened for writing was closed or file or directory created in watched directory
                    if ($mask == IN_CLOSE_WRITE || $mask == IN_CREATE) {
                        if ($this->isListening($wd)) {
                            $this->queryEvents(false, $inotifyEvents[0]);
                            return true;
                        }
                    // Watch was removed (explicitly by inotify_rm_watch() or because file was removed or
                    // filesystem unmounted
                    } else if ($mask == IN_IGNORED) {
                        $this->removeWatch($wd);
                        continue 2; # or 1 ???
                    }
                }
            }
        }

        $this->removeWatches();
        fclose($this->_fd);
        return false;
    }

    /**
     * Search for events since the last invocation of this function. Set the [[events]] array to the
     * events found. If the array is not empty, after invocation of this function, the logic should
     * return true.
     * 
     * @param bool $lost whether it's a "lost event" or not
     * @param array|null $debug array of information returned by inotify_read()
     * @return void
     */
    public function queryEvents($lost = false, $debug = null)
    {
        $time = $this->getQueryTime();

        // search for events
        $this->events = EventItem::find()
            ->where(['event' => $this->_listenEvents])
            ->andWhere(['>', 'generated_at', $time])
            ->orderBy([ 'generated_at' => SORT_ASC ])->all();

        $max = 0;
        if (!empty($this->events)) {
            foreach ($this->events as $event) {
                $event->sent_at = microtime(true);
                $event->debug = YII_ENV_DEV ? json_encode([
                    'type' => $lost ? 'lost event' : 'live event',
                    'delta' => microtime(true) - $event->generated_at,
                    'info' => $debug,
                ]) : null;

                // read the file contents if the payload was too large
                if (strpos($event->data, "file://") === 0) {
                    $file = substr($event->data, strlen("file://"));
                    if (is_readable($file)) {
                        $event->data = file_get_contents($file);
                    }
                }
                $this->sentEvents++;
            }
            // the generated_at attribute from the most recent event from database
            $this->_mostRecentEventTime = max(array_column($this->events, 'generated_at'));
        }
    }

    /**
     * Are we actively listen to the given descriptor?
     * 
     * @param resource $descriptor the descritor to check
     * @return bool true or false
     */
    public function isListening ($descriptor)
    {
        return array_search($descriptor, $this->_fwd) !== false
            || array_search($descriptor, $this->_dwd) !== false;
    }

    /**
     * Calls stream_select() to listen for activities.
     * 
     * @param array $read The streams listed in the read array will be watched to see if characters become 
     * available for reading 
     * @return integer|false  On success stream_select() returns the number of stream resources contained in
     * the modified arrays, which may be zero if the timeout expires before anything interesting happens. On
     * error FALSE is returned and a warning raised (this can happen if the system call is interrupted by an
     * incoming signal).
     */
    public function listenToStreams (&$read)
    {
        $write = null;
        $except = null;
        list($tv_sec, $tv_usec) = $this->getTimeoutForSelect();
        $this->_resumeListeningTime = microtime(true);
        $ret = stream_select($read, $write, $except, $tv_sec, $tv_usec);
        $this->_stopListeningTime = microtime(true);
        return $ret;
    }

    /**
     * Get the relevant query time timestamp.
     * 
     * @return float the timestamp
     */
    public function getQueryTime ()
    {
        if ($this->_mostRecentEventTime !== null) {
            $time = $this->_mostRecentEventTime;
        } else if ($this->_resumeListeningTime !== null) {
            $time = $this->_resumeListeningTime;
        } else if (isset($this->stopped_at)) {
            $time = $this->stopped_at;
        } else {
            $time = $this->_startTime;
        }
        return $time;
    }

    /**
     * Send idle signals
     * 
     * @return boolean Returns true if connection is normal, false otherwise (client disconnected).
     */
    public function idle ()
    {
        // needed by php to determine a connection abort by the user
        echo '0' . PHP_EOL . PHP_EOL;
        ob_flush();
        flush();
        return connection_aborted() === 0;
    }

}
