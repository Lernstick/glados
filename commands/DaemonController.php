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
     * The list has the following pattern:
     * [
     *     0 => ['controller1', 'action1'],
     *     1 => ['controller1', 'action2'],
     *     2 => ['controller2', 'action2'],
     *     ...
     *     N => ['controllerN', 'actionN']
     * ]
     */
    public $joblist = [
        0 => ['download', 'run-once'],
        1 => ['backup', 'run-once'],
        2 => ['analyze', 'run-once'],
    ];


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

    }

    /**
     * Initiates the daemon.
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
     * @param bool $state if true log also to the database, defaults to false
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

    /**
     * @inheritdoc
     */
    public function doJob()
    {
        while (true) {
            $tot = false;
            foreach ($this->joblist as $priority => $task) {
                $controller = Yii::$app->createControllerByID($task[0]);
                $controller->daemon = $this->daemon;
                $ret = $controller->runAction($task[1]);
                $controller = null;
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
            $this->log('start new daemon, workload: ' . $workload . '%, count: ' . $count . '.', true);
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
                $this->log('Caught SIGTERM, stopping...', true);
                $this->stop('SIGTERM');
                die();
            case SIGHUP:
                $this->log('Caught SIGHUP, I am alive.', true);
                $this->daemon->alive = new Expression('NOW()');
                $this->daemon->save();
                break;
            case SIGINT:
                $this->log('Caught SIGINT, stopping...', true);
                $this->stop('SIGINT');
                die();
            case SIGQUIT:
                $this->log('Caught SIGQUIT, stopping...', true);
                $this->stop('SIGINT');
                die();
            case SIGUSR1:
                $this->log('Caught SIGUSR1, reloading configuration...', true);
                $this->reloadConfig();
                break;
            default:
                // handle all other signals
        }
    }

}
