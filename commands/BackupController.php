<?php

namespace app\commands;

use yii;
use yii\db\Expression;
use app\commands\DaemonController;
use app\models\Ticket;
use app\models\Daemon;
use app\models\Activity;

/**
 * Backup Daemon (pull)
 * This is the Daemon which calls rdiff-backup to pull the data from the clients one by one.
 */
class BackupController extends DaemonController
{

    public $ticket;
    private $_cmd;
    public $remoteUser = 'root';
    #public $remotePath = '/home/user';
    public $remotePath = '/overlay/home/user';

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
    public function doJob($id = '')
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
                while (($this->ticket = $this->getNextTicket()) === null) {
                    sleep(5);
                }
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
            }else{
                $this->ticket->backup_state = 'backup in progress...';
                $this->ticket->save(false);

                $this->_cmd = "rdiff-backup --remote-schema 'ssh -i " . \Yii::$app->basePath . "/.ssh/rsa "
                     . "-o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -C %s rdiff-backup --server' "
                     . "-v5 --print-statistics "
                     . escapeshellarg($this->remoteUser . "@" . $this->ticket->ip . "::" . $this->remotePath) . " "
                     . escapeshellarg(\Yii::$app->basePath . "/backups/" . $this->ticket->token . "/") . " "
                     . "2>&1";

                $this->log('Executing rdiff-backup: ' . $this->_cmd);

                $output = array();
                $lastLine = exec($this->_cmd, $output, $retval);
                $output = implode(PHP_EOL, $output);

                if($retval != 0){
                    $this->ticket->backup_state = 'rdiff-backup failed (retval: ' . $retval . '), output: '
                         . PHP_EOL . $output;
                    $this->log($this->ticket->backup_state);
                }else{
                    $this->log($output);
                    $this->ticket->backup_last = new Expression('NOW()');
                    $this->ticket->backup_state = 'backup successful.';
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
        }

        parent::stop();
    }

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

    // clean up abandoned tickets
    private function cleanup()
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

    private function getNextTicket()
    {

        $this->cleanup();



        // now those which weren't tried in the last 5 minutes
        $query = Ticket::find()
            ->where(['not', ['start' => null]])
            ->andWhere(['end' => null])
            ->andWhere(['not', ['ip' => null]])
            ->andWhere([
                'or',
                ['<', 'backup_last_try', new Expression('NOW() - INTERVAL 5 MINUTE')],
                ['backup_last_try' => null]
            ])
            ->andWhere(['backup_lock' => 0])
            ->andWhere(['restore_lock' => 0])
            ->andWhere(['bootup_lock' => 0])
            ->orderBy(['backup_last_try' => SORT_ASC]);


        if (($ticket = $query->one()) !== null) {
            $ticket->backup_lock = 1;
            $ticket->running_daemon_id = $this->daemon->id;
            $ticket->save(false);
            return $ticket;
        }

        return null;

    }

}
