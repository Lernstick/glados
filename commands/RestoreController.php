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
use app\components\ShellCommand;
use yii\helpers\Console;


/**
 * Restore Daemon/Process (push)
 * This is the process which calls `rdiff-backup --restore-as-of` to push the data to the client.
 */
class RestoreController extends DaemonController
{

    /**
     * @var Ticket The ticket in processing at the moment 
     */
    public $ticket;

    /**
     * @var string 
     */
    public $restore;

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
    private $remotePath = '/overlay';

    /**
     * @inheritdoc
     */
    public function start ()
    {
        parent::start();
    }

    /**
     * @inheritdoc
     */
    public function doJob ()
    {
        $args = func_get_args();
        $id = $args[0];
        $file = $args[1];
        $date = $args[2];
        $restorePath = isset($args[3]) ? $args[3] : null;

        pcntl_signal_dispatch();
        $this->cleanup();

        if (($this->ticket = Ticket::findOne(['id' => $id])) == null) {
            $this->logError('Error: ticket with id ' . $id . ' not found.');
            return;
        }        

        if ($this->ticket->restore_lock != 0 || $this->ticket->backup_lock != 0) {
            $this->logError('Error: ticket with id ' . $id . ' is already in processing.');
            return;
        }

        /*if (($this->ticket = Ticket::findOne(['id' => $id, 'backup_lock' => 0, 'restore_lock' => 0])) == null) {
            $this->log('Error: ticket with id ' . $id . ' not found or it is already in processing.');
            return;
        }*/
        $this->ticket->restore_lock = 1;
        $this->ticket->running_daemon_id = $this->daemon->id;
        $this->logInfo('Processing ticket: ' .
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
                'description' => yiit('activity', 'Restore failed: network error.'),
                'severity' => Activity::SEVERITY_WARNING,
            ]);
            $act->save();
            return;
        }

        $this->remotePath = FileHelper::normalizePath($this->remotePath . '/' . $this->ticket->exam->backup_path);

        if ($file == '::Desktop::') {
            $file = trim($this->ticket->runCommand('sudo -u user xdg-user-dir DESKTOP')[0]);
        } else if ($file == '::Documents::') {
            $file = trim($this->ticket->runCommand('sudo -u user xdg-user-dir DOCUMENTS')[0]);
        }
        if (substr($file, 0, strlen($this->remotePath)) == $this->remotePath) {
            $file = FileHelper::normalizePath('/' . substr($file, strlen($this->remotePath)));
        }
        
        if (is_dir(FileHelper::normalizePath(\Yii::$app->params['backupPath'] . "/" . $this->ticket->token))) {
            $fs = new RdiffFileSystem([
                'root' => $this->remotePath,
                'location' => FileHelper::normalizePath(\Yii::$app->params['backupPath'] . "/" . $this->ticket->token),
                'restoreUser' => $this->remoteUser,
                'restoreHost' => $this->ticket->ip,
            ]);

            if($fs->slash($file) === null){
                $this->ticket->restore_state = 'Restore failed: "' . $file . '": No such file or directory.';
                $this->logError($this->ticket->restore_state);
                $this->ticket->restore_lock = 0;
                $this->ticket->save(false);

                $act = new Activity([
                        'ticket_id' => $this->ticket->id,
                        'description' => yiit('activity', 'Restore failed: "{file}": No such file or directory.'),
                        'params' => [ 'file' => $file ],
                        'severity' => Activity::SEVERITY_WARNING,
                ]);
                $act->save();

                return;
            }
        } else {
            $this->ticket->restore_state = 'Nothing to restore.';
            $this->logInfo($this->ticket->restore_state);
            $this->ticket->restore_lock = 0;
            $this->ticket->save(false);
            return;
        }

        $datetime = new \DateTime('now', new \DateTimeZone(\Yii::$app->formatter->defaultTimeZone));

        #$file = FileHelper::normalizePath($this->remotePath . '/' . $file);
        $file = empty($file) ? '/' : $file;

        $this->restore = new Restore([
            'startedAt' => $datetime->format('Y-m-d H:i:s'),
            'ticket_id' => $this->ticket->id,
            'file' => $file,
            'restoreDate' => $date == 'now' ? date('c') : $date,
        ]);

        $this->ticket->restore_state = 'restore in progress...';
        $this->ticket->save(false);

        $restorePath = $restorePath !== null ? $restorePath : '/overlay/' . $this->ticket->exam->backup_path;

        /* first command */
        $this->_cmd = "rdiff-backup --terminal-verbosity=5 --force --remote-schema "
                . "'ssh -i " . \Yii::$app->params['dotSSH'] . "/rsa "
                . "-o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -C %s rdiff-backup --server' "
             . "--create-full-path "
             . "--restore-as-of " . escapeshellarg($date) . " "
             . escapeshellarg(FileHelper::normalizePath(\Yii::$app->params['backupPath'] . "/" . $this->ticket->token . "/" . $file)) . " "
             . escapeshellarg($this->remoteUser . "@" . $this->ticket->ip . "::" . FileHelper::normalizePath($restorePath . '/' . $file)) . " "
             . "2>&1;" . " ";
             /* second command */
             //. "ssh -i " . \Yii::$app->params['dotSSH'] . "/rsa "
             //. "-o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no "
             //. escapeshellarg($this->remoteUser . "@" . $this->ticket->ip) . " "
             //. "mount -o remount,rw / ";

        $this->logInfo('Executing rdiff-backup: ' . $this->_cmd);

        $cmd = new ShellCommand($this->_cmd);

        $output = "";
        $logFile = Yii::getAlias('@runtime/logs/restore.' . $this->ticket->token . '.' . date('c') . '.log');

        $cmd->on(ShellCommand::COMMAND_OUTPUT, function($event) use (&$output, $logFile) {
            echo $this->ansiFormat($event->line, $event->channel == ShellCommand::STDOUT ? Console::NORMAL : Console::FG_RED);
            $output .= $event->line;
            file_put_contents($logFile, $event->line, FILE_APPEND);
        });

        $retval = $cmd->run();

        $this->ticket->runCommand('mount -o remount,rw /');

        if($retval != 0) {
            $this->ticket->restore_state = 'rdiff-backup failed (retval: ' . $retval . '), output: '
                 . PHP_EOL . $output;
            $this->logError($this->ticket->restore_state);

            $act = new Activity([
                    'ticket_id' => $this->ticket->id,
                    'description' => yiit('activity', 'Restore failed: rdiff-backup failed (retval: {retval})'),
                    'params' => [ 'retval' => $retval ],
                    'severity' => Activity::SEVERITY_WARNING,
            ]);
            $act->save();
        } else {
            $this->logInfo($output);
            $this->ticket->restore_state = 'restore successful.';
            $this->restore->finishedAt = new Expression('NOW()');
            $this->restore->save();
            $act = new Activity([
                    'ticket_id' => $this->ticket->id,
                    'description' => yiit('activity', 'Restore of {file} as it was as of {date} was successful.'),
                    'params' => [
                        'file' => $this->restore->file,
                        'date' => yii::$app->formatter->format($this->restore->restoreDate, 'datetime')
                    ],
                    'severity' => Activity::SEVERITY_SUCCESS,
            ]);
            $act->save();
        }

        $this->ticket->restore_lock = 0;
        $this->ticket->save(false);

    }

    /**
     * @inheritdoc
     */
    public function stop($cause = null)
    {
        parent::stop($cause);
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
                $this->logError('Port ' . $port . ' is closed or blocked. (try ' . $c . '/' . $times . ')');
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
