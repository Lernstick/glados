<?php

namespace app\commands;

use yii;
use yii\db\Expression;
use app\commands\DaemonController;
use app\models\Ticket;
use app\models\Daemon;
use app\models\Activity;
use app\models\Screenshot;
use app\models\ScreenshotSearch;
use app\components\ShellCommand;
use yii\helpers\Console;

/**
 * Backup Daemon (pull)
 * This is the daemon which calls rdiff-backup to pull the data from the clients one by one.
 */
class BackupController extends DaemonController
{

    /**
     * @var Ticket The ticket in processing at the moment 
     */
    public $ticket;

    /**
     * @var string
     */
    private $_cmd;

    /**
     * @var string The user to login at the target system
     */
    public $remoteUser = 'root';

    /**
     * @var string The path at the target system to backup
     */
    public $remotePath = '/overlay/home/user';

    /**
     * @var boolean Whether it is the last backup or not 
     */
    private $finishBackup;

    /**
     * @inheritdoc
     */
    public function start()
    {
        parent::start();
    }

    /**
     * @inheritdoc
     */
    public function doJob ($id = '')
    {

        while (true) {
            pcntl_signal_dispatch();
            $this->cleanup();

            if ($id != '') {
                if (($this->ticket =  Ticket::findOne(['id' => $id, 'backup_lock' => 0, 'restore_lock' => 0])) == null){
                    $this->log('Error: ticket with id ' . $id . ' not found, it is already in processing, or locked while booting.');
                    return;
                }
                
                if ($this->ticket->bootup_lock == 1) {
                    $this->ticket->backup_state = 'backup is locked during bootup.';
                    $this->ticket->save(false);
                    $this->ticket = null;
                    return;
                }
                $this->ticket->backup_lock = 1;
                $this->ticket->running_daemon_id = $this->daemon->id;
                $this->ticket->save(false);
            }

            if ($this->ticket == null) {
                $this->log('idle', true);
                do {
                    sleep(rand(5, 10));
                } while (($this->ticket = $this->getNextTicket()) === null);
            }

            if ($this->ticket->backup_last < $this->ticket->end) {
                $this->finishBackup = true;
            } else {
                $this->finishBackup = false;
            }

            if (!is_writable(\Yii::$app->basePath . "/backups/")) {
                $this->ticket->backup_state = \Yii::$app->basePath . '/backups/: No such file or directory or not writable.';
                $this->ticket->backup_last_try = new Expression('NOW()');
                $this->ticket->backup_lock = 0;
                $this->ticket->save(false);
                $this->log($this->ticket->backup_state);
                $this->ticket = null;
                return;
            }

            $this->log('Processing ticket: ' .
                ( empty($this->ticket->test_taker) ? $this->ticket->token : $this->ticket->test_taker) .
                ' (' . $this->ticket->ip . ')', true);
            $this->ticket->backup_state = 'connecting to client...';
            $this->ticket->save(false);

            if ($this->checkPort(22, 3) === false) {
                $this->ticket->backup_state = 'network error.';
                $this->ticket->backup_last_try = new Expression('NOW()');
                $this->ticket->backup_lock = 0;
                $this->ticket->save(false);

                $act = new Activity([
                        'ticket_id' => $this->ticket->id,
                        'description' => 'Backup failed: ' . $this->ticket->backup_state,
                ]);
                $act->save();

            }else{
                $this->ticket->backup_state = 'backup in progress...';
                if ($this->finishBackup == true) {
                    $this->ticket->runCommand('echo "backup in progress..." > /home/user/shutdown');
                }
                $this->ticket->save(false);

                $this->_cmd = "rdiff-backup --remote-schema 'ssh -i " . \Yii::$app->basePath . "/.ssh/rsa "
                     . "-o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -C %s rdiff-backup --server' "
                     . "-v5 --print-statistics "
                     . escapeshellarg($this->remoteUser . "@" . $this->ticket->ip . "::" . $this->remotePath) . " "
                     . escapeshellarg(\Yii::$app->basePath . "/backups/" . $this->ticket->token . "/") . " "
                     . "";

                $this->log('Executing rdiff-backup: ' . $this->_cmd);

                $cmd = new ShellCommand($this->_cmd);
                $output = "";
                $logFile = Yii::getAlias('@runtime/logs/backup.' . $this->ticket->token . '.' . date('c') . '.log');
                //$targetFile = Yii::getAlias('@app/backups/' . $this->ticket->token . '/rdiff-backup-data/backup.' . date('c') . '.log');

                $cmd->on(ShellCommand::COMMAND_OUTPUT, function($event) use (&$output, $logFile) {
                    echo $this->ansiFormat($event->line, $event->channel == ShellCommand::STDOUT ? Console::NORMAL : Console::FG_RED);
                    $output .= $event->line;
                    file_put_contents($logFile, $event->line, FILE_APPEND);
                });

                /*$cmd->on(ShellCommand::COMMAND_STOPPED, function($event) use ($logFile, $targetFile) {
                    if (file_exists(dirname($targetFile))) {
                        rename($logFile, $targetFile);
                    }
                });*/

                $retval = $cmd->run();

                if($retval != 0){
                    $this->ticket->backup_state = 'rdiff-backup failed (retval: ' . $retval . '), output: '
                         . PHP_EOL . $output;
                    $this->log($this->ticket->backup_state);

                    $act = new Activity([
                            'ticket_id' => $this->ticket->id,
                            'description' => 'Backup failed: rdiff-backup failed (retval: ' . $retval . ')',
                    ]);
                    $act->save();

                    if ($this->finishBackup == true) {
                        $this->ticket->runCommand('echo "backup failed, waiting for next try..." > /home/user/shutdown');
                    }
                }else{
                    //$this->log($output);
                    $this->ticket->backup_last = new Expression('NOW()');
                    $this->ticket->backup_state = 'backup successful.';
                    if ($this->finishBackup == true) {
                        $this->ticket->runCommand('echo 0 > /home/user/shutdown');
                    }

                    $searchModel = new ScreenshotSearch();
                    $dataProvider = $searchModel->search($this->ticket->token);
                    $this->log("Generating thumbnails...");
                    foreach ($dataProvider->models as $model) {
                        $model->getThumbnail();
                    }
                }

                $this->ticket->backup_last_try = new Expression('NOW()');
                $this->ticket->backup_lock = 0;
                $this->ticket->save(false);

            }

            $this->ticket = null;

            if ($id != '') {
                return;
            }

        }

    }

    /**
     * @inheritdoc
     */
    public function stop()
    {

        if ($this->ticket != null) {
            $this->ticket->backup_state = 'backup aborted.';
            $this->ticket->save(false, ['backup_state']);

            $act = new Activity([
                    'ticket_id' => $this->ticket->id,
                    'description' => 'Backup failed: ' . $this->ticket->backup_state,
            ]);
            $act->save();

        }

        parent::stop();
    }

    /**
     * Determines if a given port on the target system is open or not
     *
     * @param integer $port The port to check
     * @param integer $times The number to times to try (with 5 seconds delay inbetween every check)
     * @return boolean Whether the port is open or not
     */
    private function checkPort($port, $times = 1)
    {
        for($c=1;$c<=$times;$c++){
            $fp = @fsockopen($this->ticket->ip, $port, $errno, $errstr, 10);
            if (!$fp) {
                $this->log('Port ' . $port . ' is closed or blocked. (try ' . $c . '/' . $times . ')');
                sleep(5);
            } else {
                // port is open and available
                fclose($fp);
                return true;
            }
        }
        return false;
    }

    /**
     * Clean up abandoned tickets. If a ticket stays in backup_lock or restore_lock and
     * its associated daemon is not running anymore, this function will unlock those tickets.
     *
     * @return void
     */
    private function cleanup ()
    {

        $query = Ticket::find()
            ->where(['backup_lock' => 1])
            ->orWhere(['restore_lock' => 1]);

        $tickets = $query->all();
        foreach ($tickets as $ticket) {
            if (($daemon = Daemon::findOne($ticket->running_daemon_id)) !== null) {
                if ($daemon->running != true) {
                    $ticket->backup_lock = $ticket->restore_lock = 0;
                    $ticket->save(false);
                    $daemon->delete();
                }
            }else{
                $ticket->backup_lock = $ticket->restore_lock = 0;
                $ticket->save(false);
            }
        }
    }

    /**
     * Returns a ticket model which has the finish process initiated.
     * Those tickets only need one last backup, therefore they have higher 
     * priority against the others.
     *
     * @return Ticket|null
     */
    private function finished ()
    {
        $query = Ticket::find()
            ->where(['not', ['start' => null]])
            ->andWhere(['not', ['end' => null]])
            ->andWhere(['not', ['ip' => null]])
            ->andWhere('`backup_last` < `end`')
            ->andWhere([
                'or',
                ['<', 'backup_last_try', new Expression('NOW() - INTERVAL 1 MINUTE')],
                ['backup_last_try' => null],
                '`backup_last_try` <=> `backup_last`'
            ])
            ->andWhere(['backup_lock' => 0])
            ->andWhere(['restore_lock' => 0])
            ->andWhere(['bootup_lock' => 0])
            ->orderBy(['backup_last_try' => SORT_ASC]);

        return $query->one();
    }

    /**
     * Determines the next ticket to process
     *
     * @return Ticket|null
     */
    private function getNextTicket ()
    {

        // first do a cleanup
        $this->cleanup();

        $this->pingOthers();

        // then search for finished tickets for a last backup
        if (($ticket = $this->finished()) !== null) {
            $ticket->backup_lock = 1;
            $ticket->running_daemon_id = $this->daemon->id;
            $ticket->save(false);
            return $ticket;
        }

        // now those which weren't tried in the last n seconds (n=backup_interval)
        $query = Ticket::find()
            ->where(['not', ['start' => null]])
            ->andWhere(['end' => null])
            ->andWhere(['not', ['ip' => null]])
            ->andWhere(['not', ['backup_interval' => 0]])
            ->andWhere([
                'or',
                [
                    '<',
                    new Expression('unix_timestamp(`backup_last_try`) + `backup_interval`'),
                    new Expression('unix_timestamp(NOW())')
                ],
                ['backup_last_try' => null]
            ])
            ->andWhere(['backup_lock' => 0])
            ->andWhere(['restore_lock' => 0])
            ->andWhere(['bootup_lock' => 0])
            ->orderBy(new Expression('unix_timestamp(`backup_last_try`) + `backup_interval` ASC'));            


        // finally lock the next ticket and return it
        if (($ticket = $query->one()) !== null) {
            $ticket->backup_lock = 1;
            $ticket->running_daemon_id = $this->daemon->id;
            $ticket->save(false);
            return $ticket;
        }

        return null;

    }

}
