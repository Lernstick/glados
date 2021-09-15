<?php

namespace app\commands;

use yii;
use yii\db\Expression;
use app\commands\NetworkController;
use app\models\Ticket;
use app\models\Daemon;
use app\models\Activity;
use app\models\Restore;
use app\models\RdiffFileSystem;
use yii\helpers\FileHelper;
use app\components\ShellCommand;
use yii\helpers\Console;


/**
 * Restore Process
 *
 * This is the process which calls `rdiff-backup --restore-as-of` to push the data to the client.
 */
class RestoreController extends NetworkController
{

    /**
     * @inheritdoc
     */
    public $ticket;

    /**
     * @inheritdoc
     */
    public $lock_type = 'restore';

    /**
     * @inheritdoc
     */
    public $lock_property = 'restore_lock';

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
     * Restores files.
     *
     * @var array $args array of arguments
     * @var int $id id of the ticket ($args[0])
     * @var string $file absolute path to file within the rdiffbackup tree ($args[1]), if prepended with "rsync://"
     * the file is restored by rsync, not rdiff-backup
     * @var string $date date of the state of the file to be restored ($args[2])
     * @var string $restorePath absolute path where to restore on the client ($args[3]), if not set
     * exam->backup_path is taken
     * 
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

        if (($this->ticket = Ticket::findOne(['ticket.id' => $id])) == null) {
            $this->logError('Error: ticket with id ' . $id . ' not found.');
            return;
        }        

        if ($this->ticket->restore_lock != 0 || $this->ticket->backup_lock != 0) {
            $this->logError('Error: ticket with id ' . $id . ' is already in processing.');
            return;
        }

        if (!$this->lockItem($this->ticket)) {
            $this->logError('Error: ticket with id ' . $id . ' is already in processing (flock).');
            return;
        }

        $this->logInfo(substitute('Processing ticket: {ticket} ({ip})', [
            'ticket' => ( empty($this->ticket->test_taker) ? $this->ticket->token : $this->ticket->test_taker),
            'ip' => $this->ticket->ip,
        ]), true);
        $this->ticket->restore_state = yiit('ticket', 'Connecting to client ...');
        $this->ticket->save(false);

        if ($this->checkPort(22, 3, $emsg) === false) {
            $this->ticket->online = false;
            $this->ticket->restore_state = yiit('ticket', 'network error: {error}.');
            $this->ticket->restore_state_params = ['error' => $emsg];
            $this->ticket->save(false);

            $act = new Activity([
                'ticket_id' => $this->ticket->id,
                'description' => yiit('activity', 'Restore failed: network error, {error}.'),
                'description_params' => ['error' => $emsg],
                'severity' => Activity::SEVERITY_WARNING,
            ]);
            $act->save();
            $this->unlockItem($this->ticket);
            return;
        } else {
            $this->ticket->online = $this->ticket->runCommand('true', 'C', 10)[1] == 0 ? true : false;
            $this->ticket->save();
        }

        $this->remotePath = FileHelper::normalizePath($this->remotePath . '/' . $this->ticket->exam->backup_path);

        $rsync = false;
        if ($file == '::Desktop::') {
            $file = trim($this->ticket->runCommand('sudo -u user xdg-user-dir DESKTOP')[0]);
        } else if ($file == '::Documents::') {
            $file = trim($this->ticket->runCommand('sudo -u user xdg-user-dir DOCUMENTS')[0]);
        } else if ($file == '::All::') {
            $file = "/";
            $rsync = true;
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

            if ($fs->slash($file) === null) {
                $this->ticket->restore_state = yiit('ticket', 'Restore failed: "{file}": No such file or directory.');
                $this->ticket->restore_state_params = ['file' => $file];
                $this->logError($this->ticket->restore_state);
                $this->ticket->restore_lock = 0;
                $this->ticket->save(false);

                $act = new Activity([
                        'ticket_id' => $this->ticket->id,
                        'description' => yiit('activity', 'Restore failed: "{file}": No such file or directory.'),
                        'description_params' => [ 'file' => $file ],
                        'severity' => Activity::SEVERITY_WARNING,
                ]);
                $act->save();

                $this->unlockItem($this->ticket);
                return;
            }
        } else {
            $this->ticket->restore_state = yiit('ticket', 'Nothing to restore.');
            $this->logInfo($this->ticket->restore_state);
            $this->unlockItem($this->ticket);
            return;
        }

        $datetime = new \DateTime('now', new \DateTimeZone(\Yii::$app->formatter->defaultTimeZone));

        $file = empty($file) ? '/' : $file;

        $this->restore = new Restore([
            'startedAt' => $datetime->format('Y-m-d H:i:s'),
            'ticket_id' => $this->ticket->id,
            'file' => $file,
            'restoreDate' => $date == 'now' ? date('c') : $date,
        ]);

        $this->ticket->restore_state = yiit('ticket', 'restore in progress...');
        $this->ticket->save(false);

        $remoteSchema = $restorePath !== null ? 'rdiff-backup --server' : 'rdiff-backup-server restore';
        $restorePath = $restorePath !== null ? $restorePath : '/overlay/' . $this->ticket->exam->backup_path;
        $logfile = $this->logfile;

        /* 1st command: rdiff-backup */
        $this->_cmd = substitute("rdiff-backup --terminal-verbosity=5 --force --remote-schema 'ssh -i {identity} -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -C %s {remoteSchema}' --create-full-path {exclude} --restore-as-of {date} {localPath} {user}@{ip}::{remotePath}", [
            'identity' => FileHelper::normalizePath(\Yii::$app->params['dotSSH'] . '/rsa'),
            'remoteSchema' => $remoteSchema,
            'exclude' => (empty($exclude) ? '' : (' --exclude ' . implode($exclude, ' --exclude '))),
            'date' => escapeshellarg($date),
            'localPath' => escapeshellarg(FileHelper::normalizePath(\Yii::$app->params['backupPath'] . "/" . $this->ticket->token . "/" . $file)),
            'user' => escapeshellarg($this->remoteUser),
            'ip' => escapeshellarg($this->ticket->ip),
            'remotePath' => escapeshellarg(FileHelper::normalizePath($restorePath . '/' . $file)),
        ]);

        $this->logInfo('Executing rdiff-backup: ' . $this->_cmd);
        file_put_contents($logfile, 'Executing rdiff-backup: ' . $this->_cmd . PHP_EOL, FILE_APPEND);

        $cmd = new ShellCommand($this->_cmd);

        $cmd->on(ShellCommand::COMMAND_OUTPUT, function($event) use ($logfile) {
            echo $this->ansiFormat($event->line, $event->channel == ShellCommand::STDOUT ? Console::NORMAL : Console::FG_RED);
            file_put_contents($logfile, $event->line, FILE_APPEND);
        });

        $retval = $cmd->run();

        $this->ticket->runCommand('mount -o remount,rw /');

        if ($retval != 0) {

            $logfile = substitute('{url:logfile:log:view:type={type},token={token},date={date}}', [
                'type' => 'restore',
                'token' => $this->ticket->token,
                'date' => $this->logfileDate,
            ]);

            $this->ticket->restore_state = yiit('ticket', 'rdiff-backup failed. For more information, please check the {logfile} (retval: {retval})');
            $this->ticket->restore_state_params = [
                'retval' => $retval,
                'logfile' => $logfile,
            ];

            $this->ticket->save();
            $this->logError($this->ticket->restore_state);

            $act = new Activity([
                'ticket_id' => $this->ticket->id,
                'description' => yiit('activity', 'Restore failed: rdiff-backup failed. For more information, please check the {logfile} (retval: {retval})'),
                'description_params' => [
                    'retval' => $retval,
                    'logfile' => $logfile,
                ],
                'severity' => Activity::SEVERITY_WARNING,
            ]);
            $act->save();
        } else {
            $this->ticket->restore_state = yiit('ticket', 'restore successful.');
            $this->restore->finishedAt = new Expression('NOW()');
            $this->restore->save();
            $act = new Activity([
                    'ticket_id' => $this->ticket->id,
                    'description' => yiit('activity', 'Restore of {file} as it was as of {date} was successful.'),
                    'description_params' => [
                        'file' => $this->restore->file,
                        'date' => yii::$app->formatter->format($this->restore->restoreDate, 'datetime')
                    ],
                    'severity' => Activity::SEVERITY_SUCCESS,
            ]);
            $act->save();
        }

        /* rsync the screen_capture files (only log and m3u8 manifest files) back */
        $rsyncFile = FileHelper::normalizePath(\Yii::$app->params['scPath'] . "/" . $this->ticket->token) . '/*.m3u8';
        $glob = glob($rsyncFile, GLOB_BRACE);
        if ($rsync && array_key_exists('screen_capture', $this->ticket->exam->settings) && $this->ticket->exam->settings['screen_capture'] && !empty($glob)) {

            /* 2nd command: rsync restore screen_capture.log */
            $this->_cmd = substitute("rsync --rsync-path=\"mkdir -p /run/initramfs/backup/var/log/ && rsync\" -L --checksum --partial --progress --protect-args --rsh='ssh -i {identity} -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no' {localPath} {user}@{ip}:{remotePath}", [
                'identity' => escapeshellarg(FileHelper::normalizePath(\Yii::$app->params['dotSSH'] . '/rsa')),
                'localPath' => escapeshellarg(FileHelper::normalizePath(\Yii::$app->params['scPath'] . "/" . $this->ticket->token . "/screen_capture.log")),
                'user' => escapeshellarg($this->remoteUser),
                'ip' => escapeshellarg($this->ticket->ip),
                'remotePath' => escapeshellarg('/run/initramfs/backup/var/log/screen_capture.log'),
            ]);

            $cmd = new ShellCommand($this->_cmd);
            $logfile = $this->logfile;
            $this->logInfo('Executing rsync: ' . $this->_cmd);
            file_put_contents($logfile, 'Executing rsync: ' . $this->_cmd . PHP_EOL, FILE_APPEND);

            $cmd->on(ShellCommand::COMMAND_OUTPUT, function($event) use ($logfile) {
                echo $this->ansiFormat($event->line, $event->channel == ShellCommand::STDOUT ? Console::NORMAL : Console::FG_RED);
                file_put_contents($logfile, $event->line, FILE_APPEND);
            });

            $retval = $cmd->run();
        }

        $this->unlockItem($this->ticket);
    }

    /**
     * @inheritdoc
     */
    public function stop ($cause = null)
    {
        if ($cause != "natural" && $this->ticket != null) {
            $this->ticket->restore_state = yiit('ticket', 'Restore aborted, cause: {cause}');
            $this->ticket->restore_state_params = ['cause' => $cause];
            $this->unlockItem($this->ticket);

            $act = new Activity([
                'ticket_id' => $this->ticket->id,
                'description' => yiit('activity', 'Restore failed: restore aborted, cause: {cause}'),
                'description_params' => ['cause' => $cause],
                'severity' => Activity::SEVERITY_WARNING,
            ]);
            $act->save();
        }

        parent::stop($cause);
    }
}