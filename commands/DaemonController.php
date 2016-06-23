<?php

namespace app\commands;

use yii;
use yii\console\Controller;
use app\models\EventItem;
use app\models\Daemon;
use app\models\DaemonSearch;

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
         * Not all statements are tickable. Typically, condition expressions and argument expressions
         * are not tickable. 
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
     * initiates the daemon
     */
    public function start()
    {

        $this->daemon = new Daemon();
        $this->daemon->description = empty($this->helpSummary) ? basename(get_class($this)) : $this->helpSummary;
        $this->daemon->state = 'initializing';
        $this->daemon->pid = getmypid();
        $this->daemon->save();

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
     * stops the daemon and cleans up
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
            #TODO
            $this->log('Caught SIGHUP, does nothing, TODO...', true);
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
            # TODO
            echo "Caught SIGUSR1...\n";
            break;
        default:
        // handle all other signals
    }
}

}
