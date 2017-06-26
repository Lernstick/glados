<?php

namespace app\commands;

use yii;
use yii\console\Controller;
use app\models\EventItem;
use app\models\Daemon;
use app\models\DaemonSearch;
use yii\db\Expression;

/**
 * Daemon base controller
 */
class DaemonController extends Controller
{

    /**
     * @var string path to the file which is used to determine that the process stopped/started
     */
    private $_pidfile;

    /**
     * @var app\models\Daemon the daemon instance for db updates
     */
    public $daemon;

    /**
     * @var integer the uid and gid under which the script should be executed.
     * defaults to 33, which in debian is the www-data user.
     */
    public $uid = 33;
    public $gid = 33;

    /**
     * @inheritdoc
     */
    public function init()
    {

        /**
         * Register a tick handler that calls pcntl_signal_dispatch();
         * In doJob(), there must sometimes be manual calls of pcntl_signal_dispatch();
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


        # setup signal handlers
        pcntl_signal(SIGTERM, array(&$this, "signalHandler"));
        pcntl_signal(SIGINT,  array(&$this, "signalHandler"));
        pcntl_signal(SIGQUIT, array(&$this, "signalHandler"));

        pcntl_signal(SIGHUP,  array(&$this, "signalHandler"));
        pcntl_signal(SIGUSR1, array(&$this, "signalHandler"));

    }

    /**
     * Initiates the daemon
     *
     * @return void
     */
    public function start()
    {

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

    }

    /**
     * Stops the daemon, cleans up and finally exits the shell script
     *
     * @return void
     */
    public function stop()
    {

        $this->daemon->state = 'stopping';
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

        fclose($this->_pidfile);
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

        exit;
        
    }

    /**
     * Logs a message to the screen and (if set) to the database
     *
     * @param string $message The message to log
     * @param bool $state If true log also to the database, defaults to false
     * @return void
     */
    public function log($message, $state = false)
    {
        echo $message . PHP_EOL;
        if ($state === true){
            $this->daemon->state = $message;
            $this->daemon->save();
        }
    }

    //public function actionRun($option = false)
    public function actionRun()
    {

        $args = func_get_args();

        $this->start();

        $this->daemon->state = 'idle';
        $this->daemon->save();

        call_user_func_array(array($this, 'doJob'), $args);
        //$this->doJob($option);

        $this->stop();

    }

    /**
     * Checks whether the other running daemons are still alive and if not, 
     * kills them.
     *
     * @return void
     */
    public function pingOthers ()
    {

        /* search for daemons with alive date older than 2 minutes */
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
                    $this->log("daemon " . $daemon->pid . ": no reaction, sending SIGHUP again.", true);
                    $oldAlive = $daemon->alive;
                    $daemon->hup();
                    sleep(30);
                    $daemon->refresh();
                    if ($oldAlive != $daemon->alive) {
                       $this->log("daemon " . $daemon->pid . ": is alive now.", true);
                    } else {
                        $this->log("daemon " . $daemon->pid . ": no reaction, sending SIGTERM", true);

                        /* send SIGTERM */
                        $daemon->stop();
                        sleep(10);
                        if ($daemon->refresh() === true && $daemon->running === true) {
                            $this->log("daemon " . $daemon->pid . ": no reaction, sending SIGKILL", true);

                            /* send SIGKILL */
                            $daemon->kill();
                        }
                    }
                }
            }
        }
    }

    /*
     * This is the actual job of the daemon
     */
    //public function doJob()
    //{
    //}

    /*
     * The signal handler function
     */
    function signalHandler($sig)
    {
        switch ($sig) {
            case SIGTERM:
                $this->log('Caught SIGTERM, stopping...', true);
                $this->stop();
                die();
            case SIGHUP:
                $this->log('Caught SIGHUP, I am alive.', true);
                $this->daemon->alive = new Expression('NOW()');
                $this->daemon->save();
                break;
            case SIGINT:
                echo "INT";
                $this->log('Caught SIGINT, stopping...', true);
                $this->stop();
                die();
            case SIGQUIT:
                $this->log('Caught SIGQUIT, stopping...', true);
                $this->stop();
                die();
            case SIGUSR1:
                echo "Caught SIGUSR1, do nothing..." . PHP_EOL;
                break;
            default:
            // handle all other signals
        }
    }

}
