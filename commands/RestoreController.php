<?php

namespace app\commands;

use yii;
use yii\db\Expression;
use app\commands\DaemonController;
use app\models\Ticket;
use app\models\Daemon;
use app\models\Activity;
use app\models\Restore;
use app\models\RdiffFileSystem;
use yii\helpers\FileHelper;


/**
 * Restore Daemon/Process (push)
 * This is the Process which calls rdiff-backup --restore-as-of to push the data to the client.
 */
class RestoreController extends DaemonController
{

    public $ticket;
    public $restore;
    private $_cmd;
    public $remoteUser = 'root';
    public $remotePath = '/home/user';

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
    public function doJob($id, $file, $date)
    {
        pcntl_signal_dispatch();
        $this->cleanup();

        if (($this->ticket = Ticket::findOne(['id' => $id])) == null) {
            $this->log('Error: ticket with id ' . $id . ' not found.');
            return;
        }        

        if ($this->ticket->restore_lock != 0 || $this->ticket->backup_lock != 0) {
            $this->log('Error: ticket with id ' . $id . ' is already in processing.');
            return;
        }

        /*if (($this->ticket = Ticket::findOne(['id' => $id, 'backup_lock' => 0, 'restore_lock' => 0])) == null) {
            $this->log('Error: ticket with id ' . $id . ' not found or it is already in processing.');
            return;
        }*/
        $this->ticket->restore_lock = 1;
        $this->ticket->running_daemon_id = $this->daemon->id;
        $this->log('Processing ticket: ' .
            ( empty($this->ticket->test_taker) ? $this->ticket->token : $this->ticket->test_taker) .
            ' (' . $this->ticket->ip . ')', true);
        $this->ticket->restore_state = 'connecting to client...';
        $this->ticket->save(false);

        if ($this->checkPort(22, 3) === false) {
            $this->ticket->restore_state = 'network error.';
            $this->ticket->restore_lock = 0;
            $this->ticket->save(false);

            $act = new Activity([
                    'ticket_id' => $this->ticket->id,
                    'description' => 'Restore failed: ' . $this->ticket->restore_state,
            ]);
            $act->save();
            return;
        }

        $fs = new RdiffFileSystem([
            'root' => $this->remotePath,
            'location' => FileHelper::normalizePath(\Yii::$app->basePath . "/backups/" . $this->ticket->token),
            'restoreUser' => $this->remoteUser,
            'restoreHost' => $this->ticket->ip,
        ]);

        if($fs->slash($file) === null){
            $this->ticket->restore_state = 'Restore failed: "' . $file . '": No such file or directory.';
            $this->ticket->restore_lock = 0;
            $this->ticket->save(false);
            return;
        }

        $datetime = new \DateTime('now', new \DateTimeZone(\Yii::$app->formatter->defaultTimeZone));

        $this->restore = new Restore([
            'startedAt' => $datetime->format('Y-m-d H:i:s'),
            'ticket_id' => $this->ticket->id,
            'file' => FileHelper::normalizePath($this->remotePath . '/' . $file),
            'restoreDate' => $date,
        ]);

        $this->ticket->restore_state = 'restore in progress...';
        $this->ticket->save(false);

        $this->_cmd = "rdiff-backup --force --remote-schema 'ssh -i " . \Yii::$app->basePath . "/.ssh/rsa "
             . "-o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -C %s rdiff-backup --server' "
             . "--restore-as-of " . escapeshellarg($date) . ' '
             . escapeshellarg(\Yii::$app->basePath . "/backups/" . $this->ticket->token . "/" . $file) . ' '
             . escapeshellarg($this->remoteUser . "@" . $this->ticket->ip . "::" . $this->remotePath . '/' . $file) . ' '
             . "2>&1";

        $this->log('Executing rdiff-backup: ' . $this->_cmd);

        $output = array();
        $lastLine = exec($this->_cmd, $output, $retval);
        $output = implode(PHP_EOL, $output);

        if($retval != 0){
            $this->ticket->restore_state = 'rdiff-backup failed (retval: ' . $retval . '), output: '
                 . PHP_EOL . $output;
            $this->log($this->ticket->restore_state);
        }else{
            $this->log($output);
            $this->ticket->restore_state = 'restore successful.';
            $this->restore->finishedAt = new Expression('NOW()');
            $this->restore->save();
        }

        $this->ticket->restore_lock = 0;
        $this->ticket->save(false);


    }

    /**
     * @inheritdoc
     */
    public function stop()
    {
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

}
