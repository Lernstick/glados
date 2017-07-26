<?php

namespace app\commands;

use yii;
use yii\db\Expression;
use app\commands\DaemonController;
use app\models\Ticket;
use app\models\Daemon;
use app\models\Activity;
use app\components\ShellCommand;
use yii\helpers\FileHelper;
use yii\helpers\Console;
use app\models\BackupSearch;
use app\models\EventItem;

/**
 * Download Daemon (push)
 * This is the daemon which calls rsync to push the exam to the clients one by one.
 */
class DownloadController extends DaemonController
{

    /**
     * @var Ticket The ticket in processing at the moment 
     */
    public $ticket;

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
                if (($this->ticket =  Ticket::findOne(['id' => $id, 'download_lock' => 0, 'bootup_lock' => 1])) == null){
                    $this->log('Error: ticket with id ' . $id . ' not found, it is already in processing.');
                    return;
                }
                
                $this->ticket->download_lock = 1;
                $this->ticket->running_daemon_id = $this->daemon->id;
                $this->ticket->save(false);
            }

            if ($this->ticket == null) {
                $this->log('idle', true);
                do {
                    sleep(rand(5, 10));
                } while (($this->ticket = $this->getNextTicket()) === null);
            }

            $this->log('Processing ticket: ' .
                ( empty($this->ticket->test_taker) ? $this->ticket->token : $this->ticket->test_taker) .
                ' (' . $this->ticket->ip . ')', true);
            $this->ticket->download_state = 'connecting to client';
            $this->ticket->save(false);

            if ($this->checkPort(22, 3) === false) {
                $this->ticket->online = 1;
                $this->ticket->download_state = 'download failed: network error';
                $this->ticket->download_lock = 0;
                $this->ticket->save(false);

                $act = new Activity([
                        'ticket_id' => $this->ticket->id,
                        'description' => 'Download failed: network error',
                ]);
                $act->save();

            }else{
                $this->ticket->scenario = Ticket::SCENARIO_DOWNLOAD;
                $this->ticket->online = $this->ticket->runCommand('true', 'C', 10)[1] == 0 ? 1 : 0;

                $this->ticket->client_state = "download in progress";
                $this->ticket->runCommand('echo "download in progress" > ' . $this->remotePath . '/state');
                $this->ticket->save(false);

                $cmd = "rsync --checksum --partial --progress "
                     . "--rsh='ssh -i " . \Yii::$app->basePath . "/.ssh/rsa "
                     . " -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no' "
                     . escapeshellarg($this->ticket->exam->file) . " "
                     . escapeshellarg($this->remoteUser . "@" . $this->ticket->ip . ":" . $this->remotePath . '/squashfs/exam.squashfs') . " "
                     . "| stdbuf -oL tr '\\r' '\\n' ";

                $this->log('Executing rsync: ' . $cmd);

                $cmd = new ShellCommand($cmd);
                $output = "";
                $logFile = Yii::getAlias('@runtime/logs/download.' . $this->ticket->token . '.' . date('c') . '.log');

                $cmd->on(ShellCommand::COMMAND_OUTPUT, function($event) use (&$output, $logFile) {
                    echo $this->ansiFormat($event->line, $event->channel == ShellCommand::STDOUT ? Console::NORMAL : Console::FG_RED);
                    $output .= $event->line;
                    preg_match('/\s([0-9]+)\%/', $event->line, $match);
                    if (isset($match[1])) {
                        $this->ticket->download_progress = intval($match[1])/100;
                        $this->ticket->save();
                    }
                    file_put_contents($logFile, $event->line, FILE_APPEND);
                });

                $retval = $cmd->run();

                if($retval != 0){
                    $this->log('rsync failed (retval: ' . $retval . '), output: ' . PHP_EOL . $output);

                    $act = new Activity([
                            'ticket_id' => $this->ticket->id,
                            'description' => 'Download failed: rsync failed (retval: ' . $retval . ')',
                    ]);
                    $act->save();

                    $this->ticket->download_state = "download failed: rsync failed";
                    $this->ticket->download_lock = 0;
                    $this->ticket->save();
                }else{
                    $act = new Activity([
                        'ticket_id' => $this->ticket->id,
                        'description' => 'Exam download finished by ' . $this->ticket->ip .
                        ' from ' . ( $this->ticket->test_taker ? $this->ticket->test_taker :
                        'Ticket with token ' . $this->ticket->token ) . '.'
                    ]);
                    $act->save();
                    $this->ticket->download_progress = 1;
                    $this->ticket->client_state = "download finished";
                    $this->ticket->download_finished = new Expression('NOW()');
                    $this->ticket->download_lock = 0;
                    $this->ticket->save();

                    /* if there is a backup available, restore the latest */
                    $backupSearchModel = new BackupSearch();
                    $backupDataProvider = $backupSearchModel->search($this->ticket->token);
                    if ($backupDataProvider->totalCount > 0) {
                        $restoreDaemon = new Daemon();
                        /* run the restore daemon in the foreground */
                        $pid = $restoreDaemon->startRestore($this->ticket->id, '/', 'now', false, '/run/initramfs/backup/' . $this->ticket->exam->backup_path);
                    }

                    $this->ticket->client_state = "preparing system";
                    $this->ticket->save();

                    $cmd = "ssh -i " . \Yii::$app->basePath . "/.ssh/rsa -o "
                         . "UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no "
                         . escapeshellarg($this->remoteUser . "@" . $this->ticket->ip) . " "
                         . "'bash -s' < " . \Yii::$app->basePath . "/scripts/prepare.sh " . escapeshellarg($this->ticket->token);

                    $this->log('Executing ssh: ' . $cmd);

                    $cmd = new ShellCommand($cmd);

                    $cmd->on(ShellCommand::COMMAND_OUTPUT, function($event) use (&$output, $logFile) {
                        echo $this->ansiFormat($event->line, $event->channel == ShellCommand::STDOUT ? Console::NORMAL : Console::FG_RED);
                    });

                    $retval = $cmd->run();
                    var_dump($retval);

/*$infoFile = <<<EOF
    gladosIp="${gladosIp}"
    gladosHost="${gladosHost}"
    gladosPort="${gladosPort}"
    partitionSystem="$(blkid -l -L system)"

    urlDownload="${urlDownload}"
    urlFinish="${urlFinish}"
    urlNotify="${urlNotify}"
    urlMd5="${urlMd5}"
    urlConfig="${urlConfig}"
EOF;*/

                    $eventItem = new EventItem([
                        'event' => 'ticket/' . $this->ticket->id,
                        'priority' => 0,
                        'data' => [
                            'setup_complete' => true,
                        ],
                    ]);
                    $eventItem->generate();
                    $this->ticket->client_state = "setup complete";
                    $this->ticket->save();                    

                }

                $this->ticket->download_lock = 0;
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

            $this->ticket->download_lock = 0;
            $this->ticket->client_state = "aborted, waiting for download";
            $this->ticket->save(false, ['client_state', 'download_lock']);

            $act = new Activity([
                'ticket_id' => $this->ticket->id,
                'description' => 'Exam download aborted (server side).'
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
     * Clean up abandoned tickets. If a ticket stays in download_lock and
     * its associated daemon is not running anymore, this function will unlock them.
     *
     * @return void
     */
    private function cleanup ()
    {

        $query = Ticket::find()
            ->where(['download_lock' => 1]);

        $tickets = $query->all();
        foreach ($tickets as $ticket) {
            if (($daemon = Daemon::findOne($ticket->running_daemon_id)) !== null) {
                if ($daemon->running != true) {
                    $ticket->download_lock = 0;
                    $ticket->save(false);
                    $daemon->delete();
                }
            }else{
                $ticket->download_lock = 0;
                $ticket->save(false);
            }
        }
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
            ->orderBy('download_request ASC');

        // finally lock the next ticket and return it
        if (($ticket = $query->one()) !== null) {
            $ticket->download_lock = 1;
            $ticket->running_daemon_id = $this->daemon->id;
            $ticket->save(false);
            return $ticket;
        }

        return null;

    }



}
