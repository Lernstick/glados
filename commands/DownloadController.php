<?php

namespace app\commands;

use yii;
use yii\db\Expression;
use app\commands\NetworkController;
use app\models\Ticket;
use app\models\Daemon;
use app\models\Activity;
use app\components\ShellCommand;
use yii\helpers\FileHelper;
use yii\helpers\Console;
use app\models\BackupSearch;
use app\models\EventItem;
use app\models\DaemonInterface;
use app\models\Issue;

/**
 * Download Process
 *
 * This is the daemon which calls rsync to push the exam to the clients one by one.
 */
class DownloadController extends NetworkController implements DaemonInterface
{

    /**
     * @inheritdoc
     */
    public $ticket;

    /**
     * @inheritdoc
     */
    public $lock_type = 'download';

    /**
     * @inheritdoc
     */
    public $lock_property = 'download_lock';

    /**
     * @var string The user to login at the target system
     */
    public $remoteUser = 'root';

    /**
     * @var string The path at the target system to create the shutdown filesystem
     */
    public $remotePath = '/run/initramfs';

    /**
     * @inheritdoc
     */
    public function doJobOnce ($id = '')
    {
        if (($this->ticket = $this->getNextItem()) !== null) {
            $this->processItem($this->ticket);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function doJob ($id = '')
    {
        $this->calcLoad(0);
        while (true) {
            pcntl_signal_dispatch();
            $this->cleanup();

            if ($id != '') {
                if (($this->ticket = Ticket::findOne(['ticket.id' => $id, 'download_lock' => 0, 'bootup_lock' => 1])) == null){
                    $this->logError('Error: ticket with id ' . $id . ' not found or already in processing.');
                    return;
                }
                
                if (!$this->lockItem($this->ticket)) {
                    $this->logError('Error: ticket with id ' . $id . ' is already in processing (flock).');
                    return;
                }
            }

            if ($this->ticket == null) {
                $this->logInfo('idle', true, false);
                do {
                    sleep(rand(5, 10));
                    $this->calcLoad(0);
                } while (($this->ticket = $this->getNextItem()) === null);
            }

            $this->processItem($this->ticket);
            $this->calcLoad(1);

            if ($id != '') {
                return;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function processItem ($ticket)
    {
        $this->ticket = $ticket;
        $this->logInfo('Processing ticket (download): ' .
            ( empty($this->ticket->test_taker) ? $this->ticket->token : $this->ticket->test_taker) .
            ' (' . $this->ticket->ip . ')', true);
        $this->ticket->client_state = yiit('ticket', 'Connecting to client ...');
        $this->ticket->save(false);

        if ($this->checkPort(22, 3, $emsg) === false) {
            Issue::markAs(Issue::CLIENT_OFFLINE, $this->ticket->id);

            $this->ticket->online = false;
            $this->ticket->client_state = yiit('ticket', 'Download failed: network error, {error}.');
            $this->ticket->client_state_params = ['error' => $emsg];
            $this->ticket->save(false);

            $act = new Activity([
                'ticket_id' => $this->ticket->id,
                'description' => yiit('activity', 'Download failed: network error, {error}.'),
                'description_params' => ['error' => $emsg],
                'severity' => Activity::SEVERITY_ERROR,
            ]);
            $act->save();

            $this->unlockItem($this->ticket);
            return;

        } else {
            Issue::markAsSolved(Issue::CLIENT_OFFLINE, $this->ticket->id);

            $this->ticket->online = $this->ticket->runCommand('true', 'C', 10)[1] == 0 ? true : false;
            $this->ticket->save(false);
        }

        $this->ticket->scenario = Ticket::SCENARIO_DOWNLOAD;
        $this->ticket->client_state = yiit('ticket', 'download in progress') . ' ...';
        $this->ticket->runCommand('echo "download in progress" > ' . $this->remotePath . '/state');
        $this->ticket->save(false);

        // create a temporary directory
        $tempDir = sys_get_temp_dir() . "/" . generate_uuid();
        mkdir($tempDir);

        // all contents in this directory are rsynced to the client
        if (file_exists($this->ticket->exam->file)) {
            symlink($this->ticket->exam->file, $tempDir . "/exam.squashfs");
        }
        if (file_exists($this->ticket->exam->file2)) {
            symlink($this->ticket->exam->file2, $tempDir . "/exam.zip");
        }
        if (file_exists(\Yii::$app->params['sciptsPath'] . "/mount.sh")) {            
            symlink(\Yii::$app->params['sciptsPath'] . "/mount.sh", $tempDir . "/mount.sh");
        }

        // set -o pipefail is there because of the stdbuf pipe, else - if rsync fails - the command
        // does not fail!
        $cmd = substitute("bash -eu -o pipefail -c \"rsync -L --checksum --partial --progress --bwlimit={bwlimit} --rsh='ssh -i {identity} -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no' {localPath} {user}@{ip}:{remotePath} | stdbuf -oL tr '\\r' '\\n'\"", [
            'bwlimit' => escapeshellarg(\Yii::$app->params['examDownloadBandwith']),
            'identity' => \Yii::$app->params['dotSSH'] . "/rsa",
            'localPath' => $tempDir . '/*',
            'user' => escapeshellarg($this->remoteUser),
            'ip' => escapeshellarg($this->ticket->ip),
            'remotePath' => escapeshellarg($this->remotePath . '/squashfs/'),
        ]);

        $this->logInfo('Executing rsync: ' . $cmd);

        $cmd = new ShellCommand($cmd);
        $logfile = $this->logfile;

        @mkdir(dirname($logfile), 0755, true);

        $cmd->on(ShellCommand::COMMAND_OUTPUT, function($event) use ($logfile) {
            echo $this->ansiFormat($event->line, $event->channel == ShellCommand::STDOUT ? Console::NORMAL : Console::FG_RED);
            preg_match('/\s([0-9]+)\%/', $event->line, $match);
            if (isset($match[1])) {
                $this->ticket->download_progress = intval($match[1])/100;
                $this->ticket->save();
            }
            file_put_contents($logfile, $event->line, FILE_APPEND);
        });

        $retval = $cmd->run();

        // remove the temporary directory
        @unlink($tempDir . "/exam.squashfs");
        @unlink($tempDir . "/exam.zip");
        @unlink($tempDir . "/mount.sh");
        @rmdir($tempDir);

        if ($retval != 0) {
            $act = new Activity([
                'ticket_id' => $this->ticket->id,
                'description' => yiit('activity', 'Download failed: rsync failed (retval: {retval})'),
                'description_params' => [ 'retval' => $retval ],
                'severity' => Activity::SEVERITY_ERROR,
            ]);
            $act->save();

            $this->ticket->client_state = yiit('ticket', "Download failed: rsync failed");
        } else {

            if ($this->ticket->test_taker) {
                $act = new Activity([
                    'ticket_id' => $this->ticket->id,
                    'description' => yiit('activity', 'Exam download finished by {ip} from {test_taker}.'),
                    'description_params' => [
                        'ip' => $this->ticket->ip,
                        'test_taker' => $this->ticket->test_taker,
                    ],
                    'severity' => Activity::SEVERITY_SUCCESS,
                ]);
            } else {
                $act = new Activity([
                    'ticket_id' => $this->ticket->id,
                    'description' => yiit('activity', 'Exam download finished by {ip} from Ticket with token {token}.'),
                    'description_params' => [
                        'ip' => $this->ticket->ip,
                        'token' => $this->ticket->token,
                    ],
                    'severity' => Activity::SEVERITY_SUCCESS,
                ]);
            }
            $act->save();

            $this->ticket->download_progress = 1;
            $this->ticket->client_state = yiit('ticket', 'download finished');
            $this->ticket->download_finished = new Expression('NOW()');

            /* if there is a backup available, restore the latest */
            $backupSearchModel = new BackupSearch();
            $backupDataProvider = $backupSearchModel->search($this->ticket->token);
            if ($backupDataProvider->totalCount > 0) {
                $restoreDaemon = new Daemon();
                /* run the restore daemon in the foreground */
                /* restore all that was backed up AND the screen_capture files as well */
                $pid = $restoreDaemon->startRestore($this->ticket->id, '::All::', 'now', false, '/run/initramfs/backup/' . $this->ticket->exam->backup_path);
            }

            $this->ticket->client_state = yiit('ticket', 'preparing system');
            $this->ticket->save();

            /* run the prepare.sh script on the client */
            $cmd = substitute("cat {scripts} {prepare} | ssh -i {identity} -o "
                 . "UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no {user}@{ip} 'bash -s' {token}", [
                'scripts' => escapeshellarg(\Yii::$app->basePath . "/scripts/prepare.d/") . "*",
                'prepare' => escapeshellarg(\Yii::$app->basePath . "/scripts/prepare.sh"),
                'identity' => escapeshellarg(\Yii::$app->params['dotSSH'] . "/rsa"),
                'user' => escapeshellarg($this->remoteUser),
                'ip' => escapeshellarg($this->ticket->ip),
                'token' => escapeshellarg($this->ticket->token),
            ]);

            $this->logInfo('Executing ssh: ' . $cmd);

            $cmd = new ShellCommand($cmd);
            $logfile = $this->logfile;

            $cmd->on(ShellCommand::COMMAND_OUTPUT, function($event) use ($logfile) {
                echo $this->ansiFormat($event->line, $event->channel == ShellCommand::STDOUT ? Console::NORMAL : Console::FG_RED);
                file_put_contents($logfile, $event->line, FILE_APPEND);
            });

            $retval = $cmd->run();

            // success
            if ($retval == "0") {
                $eventItem = new EventItem([
                    'event' => 'ticket/' . $this->ticket->id,
                    'priority' => 0,
                    'data' => [
                        'setup_complete' => true,
                    ],
                ]);
                $eventItem->generate();
                $this->ticket->client_state = yiit('ticket', 'setup complete');
                $this->ticket->save();
            } else {
                $eventItem = new EventItem([
                    'event' => 'ticket/' . $this->ticket->id,
                    'priority' => 0,
                    'data' => [
                        'setup_failed' => true,
                    ],
                ]);
                $eventItem->generate();
                $this->ticket->client_state = yiit('ticket', 'setup failed');
                $this->ticket->save();
            }
        }

        $this->unlockItem($this->ticket);
        $this->ticket = null;
    }

    /**
     * @inheritdoc
     */
    public function stop($cause = null)
    {
        if ($this->ticket != null) {

            $this->ticket->download_lock = 0;
            $this->ticket->client_state = yiit('ticket', 'aborted, waiting for download');
            $this->ticket->save(false, ['client_state_id', 'client_state_data', 'download_lock']);

            $act = new Activity([
                'ticket_id' => $this->ticket->id,
                'description' => yiit('activity', 'Exam download aborted (server side), cause: {cause}'),
                'description_params' => ['cause' => $cause],
                'severity' => Activity::SEVERITY_ERROR,
            ]);
            $act->save();
        }

        parent::stop($cause);
    }

    /**
     * @inheritdoc
     *
     * Determines the next ticket to process
     *
     * @return Ticket|null
     */
    public function getNextItem ()
    {
        // first do a cleanup
        $this->cleanup();

        $this->pingOthers();

        // tickets which requested the download
        $query = Ticket::find()
            ->where(['not', ['start' => null]])
            ->andWhere(['end' => null])
            ->andWhere(['not', ['ip' => null]])
            ->andWhere(['download_lock' => 0])
            ->andWhere(['bootup_lock' => 1])
            ->andWhere(['not', ['download_request' => null]])
            ->andWhere([
                'or',
                ['download_finished' => null],
                [
                    '<',
                    new Expression('unix_timestamp(`download_finished`)'),
                    new Expression('unix_timestamp(`download_request`)')
                ],
            ])
            // only if the download was requested <=10 minutes ago,
            // else the ticket is left abandoned.
            ->andWhere([
                '>',
                'download_request',
                new Expression('NOW() - INTERVAL 10 MINUTE')
            ])
            ->orderBy('download_request ASC');

        // finally lock the next ticket and return it
        if (($ticket = $query->one()) !== null) {
            if ($this->lockItem($ticket)) {
                return $ticket;
            }
        }

        return null;
    }
}