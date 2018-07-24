<?php

namespace app\commands;

use yii;
use yii\console\Controller;
use app\models\EventItem;
use app\models\Daemon;
use app\models\DaemonSearch;
use yii\db\Expression;
use yii\helpers\Console;

/**
 * Daemon base controller
 */
class DaemonController extends Controller
{

    /**
     * @var int holds the timestamp since last invocation of [[calcLoad()]].
     */
    protected $time;

    /**
     * @var array holds 300 values 0 or 1, one value or each second in the last
     * 300 seconds. 0 means "no load", 1 means "full load" in that second. This is 
     * to calculate the load/business of the daemon in the last 5 minutes.
     */
    protected $loadarr = [];

    /**
     * @var array An array holding the timestamp of the last invocation of a job in
     * [[joblist]]. The key corresponds to the key in [[joblist]] and the value is
     * the timestamp of the last invocation.
     */
    public $jobLastRun = [];

    /**
     * @var app\models\Daemon the daemon instance for db updates
     */
    public $daemon;

    /**
     * @var string path to the file which is used to determine that the process stopped/started
     */
    public $_pidfile;

    /**
     * @var integer the uid and gid under which the script should be executed.
     * defaults to 33, which in debian is the www-data user.
     */
    public $uid = 33;
    public $gid = 33;

    /**
     * @inheritdoc
     */
    public $defaultAction = 'run';

    /**
     * @var array list of controllers and actions to work trough in one iteration.
     * The interval (in seconds) describes that the action should be executed only
     * after that interval has passed. This is optional.
     *
     * The list has the following pattern:
     * [
     *     0 => ['controller1', 'action1', interval1],
     *     1 => ['controller1', 'action2'],
     *     2 => ['controller2', 'action2', interval3],
     *     ...
     *     N => ['controllerN', 'actionN', intervalN]
     * ]
     *
     */
    public $joblist = [
        0 => ['download', 'run-once'],
        1 => ['backup', 'run-once'],
        2 => ['analyze', 'run-once'],
        3 => ['dbclean', 'run-once', 300],
    ];

    public $daemonInfo = 'daemon:0';
    public $logFile;
    public $errorLogFile;
    public $logFileIsWritable = true;

    /**
     * @inheritdoc
     */
    public function init()
    {

        /**
         * Register a tick handler that calls pcntl_signal_dispatch();
         * In [[doJob()]], there must sometimes be manual calls of pcntl_signal_dispatch();
         * A tick is an event that occurs for every N low-level tickable statements executed by
         * the parser within the declare block. The value for N is specified using ticks=N within
         * the declare block's directive section.
         * 
         * Not all statements are tickable. Typically, condition expressions and argument
         * expressions are not tickable. 
         */
        declare(ticks=1);

        # change to www-data user, gid must be set frist
        posix_setgid($this->gid);
        posix_setuid($this->uid);

        # Change process group id to its own one, so SIGINT will not be sent to all processes
        # in the process group (this would happen, when the daemon starts new daemons, even if
        # the new daemons have another parent pid, such as 1)
        posix_setpgid(getmypid(), getmypid());

        $this->time = round(time());

        # setup signal handlers
        pcntl_signal(SIGTERM, array(&$this, "signalHandler"));
        pcntl_signal(SIGINT,  array(&$this, "signalHandler"));
        pcntl_signal(SIGQUIT, array(&$this, "signalHandler"));

        pcntl_signal(SIGHUP,  array(&$this, "signalHandler"));
        pcntl_signal(SIGUSR1, array(&$this, "signalHandler"));

        # determine daemon info, e.g: [daemon:1234], where "daemon" is the prefix of the
        # controller and 1234 is the pid of the daemon, used to identify lines in the logfile.
        $tmp = explode('\\', str_replace('controller', '', strtolower(get_called_class())));
        $this->daemonInfo = end($tmp) . ':' . getmypid();
    }

    /**
     * Initiates the daemon.
     *
     * @return void
     */
    public function start()
    {

        posix_getpwuid($this->uid);
        if (!is_writable(\Yii::$app->params['daemonLogFilePath'])) {
            $this->logFileIsWritable = false;
            $this->logError('Warning: ' . \Yii::$app->params['daemonLogFilePath'] . '/ is '
                . 'not writable. '
                . 'Please make sure the directory is writable for the user under which '
                . 'the web-server is running (' . posix_getpwuid($this->uid)['name'] . '). '
                . 'This process will continue without logging to the filesystem.',
                false, false);
        }   

        $this->daemon = new Daemon();
        $this->daemon->description = empty($this->helpSummary) ? basename(get_class($this)) : $this->helpSummary;
        $this->daemon->state = 'initializing';
        $this->daemon->pid = getmypid();
        $this->daemon->save();
        $this->daemon->refresh();

        $daemons = new DaemonSearch();
        $runningDaemons = $daemons->search([])->totalCount;

        $eventItem = new EventItem([
            'event' => 'runningDaemons',
            'priority' => 0,
            'concerns' => ['users' => ['ALL']],
            'data' => [
                'runningDaemons' => $runningDaemons,
            ],
        ]);
        $eventItem->generate();

        $eventItem = new EventItem([
            'event' => 'daemon/*',
            'priority' => 0,
            'concerns' => ['users' => ['ALL']],
            'data' => [
                'status' => 'started',
                'item' => 'daemon/' . $this->daemon->pid,
                'source' => 'db',
            ],
        ]);
        $eventItem->generate();

        $this->_pidfile = fopen('/tmp/user/daemon/' . $this->daemon->pid, 'w');
        $this->logInfo('Started with pid: ' . $this->daemon->pid);
    }

    /**
     * Stops the daemon, cleans up and finally exits the shell script
     *
     * @return void
     */
    public function stop($cause = null)
    {

        $this->daemon->state = 'stopping, Cause: ' . $cause;
        $this->daemon->save();

        $daemons = new DaemonSearch();
        $runningDaemons = $daemons->search([])->totalCount;

        $eventItem = new EventItem([
            'event' => 'runningDaemons',
            'priority' => 0,
            'concerns' => ['users' => ['ALL']],
            'data' => [
                'runningDaemons' => $runningDaemons - 1,
            ],
        ]);
        $eventItem->generate();

        @fclose($this->_pidfile);
        @unlink('/tmp/user/daemon/' . $this->daemon->pid);

        $pid = $this->daemon->pid;
        $this->daemon->delete();

        $eventItem = new EventItem([
            'event' => 'daemon/*',
            'priority' => 0,
            'concerns' => ['users' => ['ALL']],
            'data' => [
                'status' => 'stopped',
                'item' => 'daemon/' . $pid,
                'source' => 'db',
            ],
        ]);
        $eventItem->generate();

        $this->logInfo('Stopped, cause: ' . $cause);
        #exit;
    }

    /**
     * TODO
     */
    public function reloadConfig()
    {
        #$this->params = require(\Yii::$app->basePath . '/config/params.php');
    }

    /**
     * Logs a message to the screen and (if set) to the database
     *
     * @param string $message the message to log
     * @param bool $error if true the line will be printed red, and stored in the 
     * error log file, defaults to false.
     * @param bool $toDB if true, log the message to the database, defaults to false
     * @param bool $toFile if true, log the message to the log file, defaults to true
     * @param bool $toScreen if true, log the message to the standard output, defaults to true
     * @return void
     */
    public function log($message, $error = false, $toDB = false, $toFile = true, $toScreen = true)
    {
        if ($toScreen === true) {
            echo $this->ansiFormat($message . PHP_EOL, $error == false ? Console::NORMAL : Console::FG_RED);
        }

        if ($toDB === true){
            $this->daemon->state = $message;
            $this->daemon->save(false);
        }

        if ($toFile === true && $this->logFileIsWritable === true) {

            if ($this->logFile === null || $this->errorLogFile === null) {
                $this->logFile = \Yii::$app->params['daemonLogFilePath'] . '/glados.' . date('Y-m-dO') . '.log';
                $this->errorLogFile = \Yii::$app->params['daemonLogFilePath'] . '/error.' . date('Y-m-dO') . '.log';
            }

            $logFile = $error == false ? $this->logFile : $this->errorLogFile;
            $line = date('r') . ' [' . $this->daemonInfo . ']' . ' - ' . $message . PHP_EOL;
            file_put_contents($logFile, $line, FILE_APPEND);
        }
    }

    /**
     * @see [[log]]
     */
    public function logError($message, $toDB = false, $toFile = true, $toScreen = true)
    {
        $this->log($message, true, $toDB, $toFile, $toScreen);
    }

    /**
     * @see [[log]]
     */
    public function logInfo($message, $toDB = false, $toFile = true, $toScreen = true)
    {
        $this->log($message, false, $toDB, $toFile, $toScreen);
    }

    /**
     * Default action to run the daemon.
     *
     * @param mixed $args all sorts of arguments can be given to that function.
     * All arguments will be passed to [[doJob()]] as they are. 
     * @return void
     */
    public function actionRun()
    {

        $args = func_get_args();

        $this->start();

        $this->daemon->state = 'idle';
        $this->daemon->save();

        call_user_func_array(array($this, 'doJob'), $args);

        $this->stop('natural');

    }

    /**
     * Default action to run just one iteration of the daemon.
     *
     * @param mixed $args all sorts of arguments can be given to that function.
     * All arguments will be passed to [[doJobOnce()]] as they are. 
     * @return mixed returns the return value of [[doJobOnce()]].
     */
    public function actionRunOnce()
    {

        $args = func_get_args();

        $this->daemon->state = 'idle';
        $this->daemon->save();

        return call_user_func_array(array($this, 'doJobOnce'), $args);

    }

    /**
     * Checks whether the other running daemons are still alive and if not, 
     * tries to stop and the kills them.
     *
     * @return void
     */
    public function pingOthers ()
    {

        /* search for daemons that are not running anymore and reap them */
        $query = Daemon::find()
            ->where(['not', ['pid' => $this->daemon->pid]])
            ->orderBy(['alive' => SORT_ASC]);

        $models = $query->all();
        foreach($models as $daemon) {
            if ($daemon->running !== true) {
                $this->logError("daemon " . $daemon->pid . ": not running anymore, seems to be crashed", true);
                $daemon->delete();

                $daemons = new DaemonSearch();
                $runningDaemons = $daemons->search([])->totalCount;

                $eventItem = new EventItem([
                    'event' => 'runningDaemons',
                    'priority' => 0,
                    'concerns' => ['users' => ['ALL']],
                    'data' => [
                        'runningDaemons' => $runningDaemons,
                    ],
                ]);
                $eventItem->generate();
            }
        }

        /* search for daemons with alive date older than 2 minutes and ping them */
        $query = Daemon::find()
            ->where(['not', ['pid' => $this->daemon->pid]])
            ->andWhere([
                'or',
                ['<', 'alive', new Expression('NOW() - INTERVAL 2 MINUTE')],
                ['alive' => null]
            ])
            ->orderBy(['alive' => SORT_ASC]);

        $models = $query->all();
        foreach($models as $daemon) {
            if ($daemon->running === true) {
                $oldAlive = $daemon->alive;

                /* send SIGHUP */
                $daemon->hup();
                sleep(5);
                $daemon->refresh();
                if ($oldAlive == $daemon->alive && $daemon->running === true) {
                    $this->logInfo("daemon " . $daemon->pid . ": no reaction, sending SIGHUP again.", true);
                    $oldAlive = $daemon->alive;
                    $daemon->hup();
                    sleep(30);
                    $daemon->refresh();
                    if ($oldAlive != $daemon->alive) {
                       $this->logInfo("daemon " . $daemon->pid . ": is alive now.", true);
                    } else {
                        $this->logError("daemon " . $daemon->pid . ": no reaction, sending SIGTERM", true);

                        /* send SIGTERM */
                        $daemon->stop();
                        sleep(10);
                        if ($daemon->refresh() === true && $daemon->running === true) {
                            $this->logError("daemon " . $daemon->pid . ": no reaction, sending SIGKILL", true);

                            /* send SIGKILL */
                            $daemon->kill();
                        }
                    }
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function doJob()
    {
        while (true) {
            $tot = false;
            foreach ($this->joblist as $priority => $task) {

                // bail out if the task has an interval set and its not reached already
                if (isset($task[2]) && array_key_exists($priority, $this->jobLastRun)) {
                    if ($this->jobLastRun[$priority] > microtime(true) - $task[2]) {
                        continue;
                    }
                }
                $controller = Yii::$app->createControllerByID($task[0]);
                $controller->daemon = $this->daemon;
                $ret = $controller->runAction($task[1]);
                $controller = null;

                $this->jobLastRun[$priority] = microtime(true);

                if ($ret === true) {
                    $this->calcLoad(1);
                    $tot = true;
                } else {
                    #$this->calcLoad(0);
                }
            }

            # If no controller has a job to do
            if ($tot === false) {
                sleep(5);
                $this->calcLoad(0);
            }
        }
    }

    /**
     * Update the load calculation in the last [0,5] minutes
     *
     * @param int $value can be 0 or 1:
     *                    - 0 means to count no load since last invokation
     *                    - 1 means to count full load since last invokation
     * @return void
     */
    protected function calcLoad ($value)
    {   
        $amount = round(time() - $this->time);
        $this->loadarr = array_merge(array_fill(0, $amount, $value), $this->loadarr);
        $this->time += $amount;
        $this->loadarr = array_slice($this->loadarr, 0, 300);

        if (count($this->loadarr) != 0) {
            $this->daemon->load = array_sum($this->loadarr)/count($this->loadarr);
        } else {
            $this->daemon->load = 0;
        }
        $this->daemon->save();
        if ($this->daemon->description == 'Daemon base controller') {
            $this->judgement();
        }
    }

    /**
     * Start/stop daemons based on thresholds
     *
     * @return void
     */
    private function judgement ()
    {
        $sum = Daemon::find()->where(['description' => 'Daemon base controller'])->sum('`load`');
        $count = Daemon::find()->where(['description' => 'Daemon base controller'])->count();
        $workload = $count != 0 ? round(100*$sum/$count) : 0;

        if (($workload > \Yii::$app->params['upperBound'] && $count < \Yii::$app->params['maxDaemons']) || $count < \Yii::$app->params['minDaemons']) {
            # start a new daemon
            $this->logInfo('Start new daemon, workload: ' . $workload . '%, count: ' . $count . '.', true);
            $daemon = new Daemon();
            $daemon->startDaemon();
        } else if ($workload < \Yii::$app->params['lowerBound'] && $count > \Yii::$app->params['minDaemons']) {
            # stop after 5 minutes
            if (time() - strtotime($this->daemon->started_at) > 300) {
                $this->stop('Load threshold');
                die();
            }
        }
    }

    /*
     * The signal handler function
     */
    public function signalHandler($sig)
    {
        switch ($sig) {
            case SIGTERM:
                $this->logInfo('Caught SIGTERM, stopping...', true);
                $this->stop('SIGTERM');
                die();
            case SIGHUP:
                $this->logInfo('Caught SIGHUP, I am alive.', true, false);
                $this->daemon->alive = new Expression('NOW()');
                $this->daemon->save();
                break;
            case SIGINT:
                $this->logInfo('Caught SIGINT, stopping...', true);
                $this->stop('SIGINT');
                die();
            case SIGQUIT:
                $this->logInfo('Caught SIGQUIT, stopping...', true);
                $this->stop('SIGINT');
                die();
            case SIGUSR1:
                $this->logInfo('Caught SIGUSR1, reloading configuration...', true);
                $this->reloadConfig();
                break;
            default:
                // handle all other signals
        }
    }

}
