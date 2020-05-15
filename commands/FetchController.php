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
use yii\helpers\FileHelper;
use yii\helpers\Console;
use app\models\DaemonInterface;

/**
 * Fetching Daemon (pull)
 * This is the daemon which calls rsync to pull the data from the clients one by one.
 * It also removes fetched files from the client (see rsync's option --remove-source-files)
 */
class FetchController extends DaemonController
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
    public $remotePath = '/overlay';

    /**
     * @var array files and directories to fetch
     */
    public $fetchList = [
    ];

    /**
     * @var string Bandwidth limit for rsync, set 0 for no limit
     */
    public $bwlimit = '10m'; // 10MB per second, set 0 for no limit

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

        $this->calcLoad(0);

        if ($id != '') {

            if (($this->ticket = Ticket::findOne($id)) == null){
                $this->logError('Error: ticket with id ' . $id . ' not found.');
                return;
            }

            $this->processItem($this->ticket);
            $this->calcLoad(1);
        }
    }

    /**
     * @inheritdoc
     */
    public function processItem ($ticket)
    {
        $this->ticket = $ticket;

        if (!is_writable(\Yii::$app->params['scPath'])) {
            $this->ticket->backup_state = yiit('ticket', '{path}: No such file or directory or not writable.');
            $this->ticket->backup_state_params = [ 'path' => \Yii::$app->params['scPath'] ];
            $this->logError($this->ticket->backup_state);
            $this->ticket->save(false);
            $this->ticket = null;
            return;
        }

        /**
         * set remotePath to nothing, because rsync together with --remove-source-files, will then remove
         * source files inside /overlay - which is the upper dir of overlayfs. This breaks the file, resulting
         * in a "Stale file handle". The filename in unusable then.
         */
        $this->remotePath = '';

        /* Generate fetch list based on the properties of the exam */
        if (array_key_exists('screen_capture', $this->ticket->exam->settings) && $this->ticket->exam->settings['screen_capture']) {
            $this->fetchList[] = FileHelper::normalizePath($this->remotePath . '/' . $this->ticket->exam->settings['screen_capture_path']) . '/launch/*';
        }

        if (array_key_exists('keylogger', $this->ticket->exam->settings) && $this->ticket->exam->settings['keylogger']) {
            $this->fetchList[] = FileHelper::normalizePath($this->remotePath . '/' . $this->ticket->exam->settings['keylogger_path']) . '/launch/*';
        }

        $this->fetchList = array_unique($this->fetchList);

        if (!empty($this->fetchList)) {

            $this->logInfo('Processing ticket (fetch): ' .
                ( empty($this->ticket->test_taker) ? $this->ticket->token : $this->ticket->test_taker) .
                ' (' . $this->ticket->ip . ')', true);
            $this->ticket->backup_state = yiit('ticket', 'fetch in progress...');
            $this->ticket->save(false);

            $fetchList = $this->fetchList;
            /* Escape the whole excludeList */
            array_walk($fetchList, function(&$e, $key){
                $e = escapeshellarg($this->remoteUser . "@" . $this->ticket->ip . ":" . $e);
            });

            $this->_cmd = "rsync -L --checksum --partial --progress --protect-args "
                . "--bwlimit=" . escapeshellarg($this->bwlimit) . " "
                . "--rsh='ssh -i " . \Yii::$app->params['dotSSH'] . "/rsa "
                . " -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no' "
                . "--remove-source-files "
                . implode($fetchList, ' ') . ' '
                . escapeshellarg(\Yii::$app->params['scPath'] . "/" . $this->ticket->token . "/") . " ";

            $this->logInfo('Executing rsync: ' . $this->_cmd);

            $cmd = new ShellCommand($this->_cmd);
            $output = "";
            $logFile = Yii::getAlias('@runtime/logs/fetch.' . $this->ticket->token . '.' . date('c') . '.log');

            $cmd->on(ShellCommand::COMMAND_OUTPUT, function($event) use (&$output, $logFile) {
                echo $this->ansiFormat($event->line, $event->channel == ShellCommand::STDOUT ? Console::NORMAL : Console::FG_RED);
                $output .= $event->line;
                file_put_contents($logFile, $event->line, FILE_APPEND);
            });

            $retval = $cmd->run();

            if ($retval != 0) {
                $this->ticket->backup_state = yiit('ticket', 'rsync failed (retval: {retval}), output: {output}');
                $this->ticket->backup_state_params = [
                    'retval' => $retval,
                    'output' => $output,
                ];
                $this->logError($this->ticket->backup_state);

                $act = new Activity([
                    'ticket_id' => $this->ticket->id,
                    'description' => yiit('activity', 'Fetch failed: rsync failed (retval: {retval})'),
                    'description_params' => [ 'retval' => $retval ],
                    'severity' => Activity::SEVERITY_WARNING,
                ]);
                $act->save();

                $this->backup_failed();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function stop ($cause = null)
    {

        if ($cause != "natural" && $this->ticket != null) {
            $this->ticket->backup_state = yiit('ticket', 'fetch aborted.');
            $this->ticket->save();

            $act = new Activity([
                    'ticket_id' => $this->ticket->id,
                    'description' => yiit('activity', 'Fetch failed: backup aborted.'),
                    'severity' => Activity::SEVERITY_WARNING,
            ]);
            $act->save();

            $this->backup_failed();

        }

        parent::stop($cause);
    }

    /**
     * If ticket is abandoned make an activity entry
     *
     * @return void
     */
    private function backup_failed()
    {
        if ($this->ticket->abandoned == true) {
            $act = new Activity([
                    'ticket_id' => $this->ticket->id,
                    'description' => yiit('activity', 'Backup failed: leaving ticket in abandoned state.'),
                    'severity' => Activity::SEVERITY_ERROR,
            ]);
            $act->save();
        }
    }

}
