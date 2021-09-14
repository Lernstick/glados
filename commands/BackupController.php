<?php

namespace app\commands;

use yii;
use yii\db\Expression;
use app\commands\NetworkController;
use app\models\Ticket;
use app\models\Daemon;
use app\models\Activity;
use app\models\Screenshot;
use app\models\ScreenshotSearch;
use app\components\ShellCommand;
use yii\helpers\FileHelper;
use yii\helpers\Console;
use app\models\DaemonInterface;
use app\models\Issue;

/**
 * Backup Process
 *
 * This is the daemon which calls rdiff-backup to pull the data from the clients one by one.
 */
class BackupController extends NetworkController implements DaemonInterface
{

    /**
     * @inheritdoc
     */
    public $ticket;

    /**
     * @inheritdoc
     */
    public $lock_type = 'backup';

    /**
     * @inheritdoc
     */
    public $lock_property = 'backup_lock';

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
     * @var boolean Whether it is the last backup or not 
     */
    private $finishBackup = false;

    /**
     * @var boolean Whether the backup is manually or automatic started
     */
    private $manualBackup = false;

    /**
     * @var array files and directories to exclude in the backup
     */
    public $excludeList = [
        '/overlay/tmp',
        '/overlay/eth0',
        '/overlay/wlan0',
        '/overlay/booted',
        '/overlay/info',
        '/overlay/overlay',
        '/overlay/init',
        '/overlay/var',
        '/overlay/media',
        '/overlay/home/user/shutdown',
        '/overlay/home/user/Schreibtisch/finish_exam.desktop',
        '/overlay/usr/bin/finishExam',
        '/overlay/etc/NetworkManager/dispatcher.d/02searchExamServer'
    ];

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
            $this->remotePath = '/overlay';

            if ($id != '') {
                if (($this->ticket = Ticket::findOne(['ticket.id' => $id, 'backup_lock' => 0, 'restore_lock' => 0])) == null){
                    $this->logError('Error: ticket with id ' . $id . ' not found, it is already in processing, or locked while booting.');
                    return;
                }
                
                // a manual backup should be triggered anyway
                /*if ($this->ticket->bootup_lock == 1) {
                    $this->ticket->backup_state = yiit('ticket', 'backup is locked during bootup.');
                    $this->ticket->save(false);
                    $this->ticket = null;
                    return;
                }*/
                if ($this->lockItem($this->ticket)) {
                    $this->manualBackup = true;
                } else {
                    $this->logError('Error: ticket with id ' . $id . ' not found, it is already in processing, or locked (flock).');
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

        if (!is_writable(\Yii::$app->params['backupPath'])) {
            $this->ticket->backup_state = yiit('ticket', '{path}: No such file or directory or not writable.');
            $this->ticket->backup_state_params = [ 'path' => \Yii::$app->params['backupPath'] ];
            $this->ticket->backup_last_try = new Expression('NOW()');
            $this->unlockItem($this->ticket);
            $this->logError($this->ticket->backup_state);
            $this->ticket = null;
            return;
        }

        $this->logInfo('Processing ticket (backup): ' .
            ( empty($this->ticket->test_taker) ? $this->ticket->token : $this->ticket->test_taker) .
            ' (' . $this->ticket->ip . ')', true);
        $this->ticket->backup_state = yiit('ticket', 'Connecting to client ...');
        $this->ticket->save(false);

        if ($this->checkPort(22, 3, $emsg) === false) {
            $this->ticket->backup_state = yiit('ticket', 'network error: {error}.');
            $this->ticket->backup_state_params = ['error' => $emsg];
            $this->ticket->backup_last_try = new Expression('NOW()');
            $this->ticket->online = false;
            $this->ticket->save(false);

            Issue::markAs(Issue::CLIENT_OFFLINE, $this->ticket->id);

            $act = new Activity([
                'ticket_id' => $this->ticket->id,
                'description' => yiit('activity', 'Backup failed: network error (ip:{ip}), {error}.'),
                'description_params' => ['ip' => $this->ticket->ip, 'error' => $emsg],
                'severity' => Activity::SEVERITY_WARNING,
            ]);
            $act->save();

            $this->backup_failed();

            $this->unlockItem($this->ticket);
            return;
        } else {
            Issue::markAsSolved(Issue::CLIENT_OFFLINE, $this->ticket->id);
            $this->ticket->online = $this->ticket->runCommand('true', 'C', 10)[1] == 0 ? true : false;
            $this->ticket->save(false);
        }

        $this->ticket->backup_state = yiit('ticket', 'backup in progress...');
        if ($this->finishBackup == true) {
            $this->ticket->runCommand('echo "backup in progress..." > /home/user/shutdown');
        }
        $this->ticket->save(false);

        # disable screen capture service on the client
        if ($this->finishBackup == true) {
            $this->ticket->runCommand('service screen_capture stop; service keylogger stop', 'C', 20);
        }

        /* Exclude screen_capture_path from backup */
        if (array_key_exists('screen_capture', $this->ticket->exam->settings)
            && $this->ticket->exam->settings['screen_capture']
        ) {
            $this->excludeList[] = FileHelper::normalizePath(
                $this->remotePath . '/' . $this->ticket->exam->settings['screen_capture_path']);
        }

        $this->remotePath = FileHelper::normalizePath($this->remotePath . '/' . $this->ticket->exam->backup_path);

        /* Generate exclude list based on remotePath */
        $exclude = array_filter($this->excludeList, function($v){
            return (strpos($v, $this->remotePath) === 0);
        });

        /* Escape the whole excludeList */
        array_walk($exclude, function(&$e, $key){
            $e = escapeshellarg($e);
        });

        $this->_cmd = substitute("rdiff-backup --remote-schema 'ssh -i {identity} -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -C %s {remoteSchema}' -v5 --print-statistics {exclude} {user}@{ip}::{remotePath} {localPath}", [
            'identity' => FileHelper::normalizePath(\Yii::$app->params['dotSSH'] . '/rsa'),
            'remoteSchema' => 'rdiff-backup-server',
            'exclude' => (empty($exclude) ? '' : (' --exclude ' . implode($exclude, ' --exclude '))),
            'user' => escapeshellarg($this->remoteUser),
            'ip' => escapeshellarg($this->ticket->ip),
            'remotePath' => escapeshellarg($this->remotePath),
            'localPath' => escapeshellarg(FileHelper::normalizePath(\Yii::$app->params['backupPath'] . "/" . $this->ticket->token . "/")),
        ]);

        $this->logInfo('Executing rdiff-backup: ' . $this->_cmd);

        $cmd = new ShellCommand($this->_cmd);
        $logfile = $this->logfile;

        $cmd->on(ShellCommand::COMMAND_OUTPUT, function($event) use ($logfile) {
            echo $this->ansiFormat($event->line, $event->channel == ShellCommand::STDOUT ? Console::NORMAL : Console::FG_RED);
            file_put_contents($logfile, $event->line, FILE_APPEND);
        });

        $retval = $cmd->run();

        if ($retval != 0) {

            $logfile = substitute('{url:logfile:log:view:type={type},token={token},date={date}}', [
                'type' => 'backup',
                'token' => $this->ticket->token,
                'date' => $this->logfileDate,
            ]);

            $this->ticket->backup_state = yiit('ticket', 'rdiff-backup failed. For more information, please check the {logfile} (retval: {retval})');
            $this->ticket->backup_state_params = [
                'retval' => $retval,
                'logfile' => $logfile,
            ];
            $this->ticket->save();
            $this->logError($this->ticket->backup_state);

            $act = new Activity([
                'ticket_id' => $this->ticket->id,
                'description' => yiit('activity', 'Backup failed: rdiff-backup failed. For more information, please check the {logfile} (retval: {retval})'),
                'description_params' => [
                    'retval' => $retval,
                    'logfile' => $logfile,
                ],
                'severity' => Activity::SEVERITY_WARNING,
            ]);
            $act->save();

            $this->backup_failed();

            if ($this->finishBackup == true) {
                $this->ticket->runCommand('echo "backup failed, waiting for next try..." > /home/user/shutdown');
            }
        } else {

            /* run the fetch daemon in the foreground */
            $this->logInfo("Fetching data...");
            $fetchDaemon = new Daemon();
            $pid = $fetchDaemon->startFetch($this->ticket->id, false);

            $this->ticket->backup_last = new Expression('NOW()');
            $this->ticket->backup_state = yiit('ticket', 'backup successful.');

            Issue::markAsSolved(Issue::LONG_TIME_NO_BACKUP, $this->ticket->id);

            $act = new Activity([
                'ticket_id' => $this->ticket->id,
                'description' => yiit('activity', 'Backup successful.'),
                'severity' => Activity::SEVERITY_INFORMATIONAL,
            ]);
            $act->save();

            # If it's the last backup, tell the client and set last_backup to 1
            if ($this->finishBackup == true) {
                $this->ticket->last_backup = 1;
                $this->ticket->save(false);
            }

            if ($this->ticket->last_backup == 1){
                $this->ticket->runCommand('echo 0 > /home/user/shutdown');
            }

            # Generate Thumbnails
            $searchModel = new ScreenshotSearch();
            $dataProvider = $searchModel->search($this->ticket->token);
            $this->logInfo("Generating thumbnails...");
            foreach ($dataProvider->models as $model) {
                $model->getThumbnail();
            }

            # Calculate the size
            $this->logInfo("Calculate backup size...");
            $this->ticket->backup_size = $this->directorySize(\Yii::$app->params['backupPath'] . "/" . $this->ticket->token) - $this->directorySize(\Yii::$app->params['backupPath'] . "/" . $this->ticket->token . '/rdiff-backup-data');
            if (is_dir(\Yii::$app->params['scPath'] . "/" . $this->ticket->token)) {
                $this->ticket->sc_size = $this->directorySize(\Yii::$app->params['scPath'] . "/" . $this->ticket->token);
            }
        }

        $this->ticket->backup_last_try = new Expression('NOW()');
        $this->unlockItem($this->ticket);
        $this->ticket = null;
    }

    /**
     * @inheritdoc
     */
    public function stop ($cause = null)
    {
        if ($cause != "natural" && $this->ticket != null) {
            $this->ticket->backup_state = yiit('ticket', 'Backup aborted, cause: {cause}');
            $this->ticket->backup_state_params = ['cause' => $cause];
            $this->unlockItem($this->ticket);

            $act = new Activity([
                'ticket_id' => $this->ticket->id,
                'description' => yiit('activity', 'Backup failed: backup aborted, cause: {cause}'),
                'description_params' => ['cause' => $cause],
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

    /**
     * Returns a ticket model which has the finish process initiated.
     * Those tickets only need one last backup, therefore they have higher 
     * priority against the others.
     *
     * @return Ticket|null
     */
    private function finished ()
    {
        $query = $this->queryNotAbandoned()
            ->andWhere(['not', ['start' => null]])
            ->andWhere(['not', ['end' => null]])
            ->andWhere(['not', ['ip' => null]])
            ->andWhere(['last_backup' => 0])
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
     * @inheritdoc
     *
     * Determines the next ticket to process
     *
     * @return Ticket|null
     */
    public function getNextItem ()
    {
        // re-set the finishBackup to false
        $this->finishBackup = false;

        // first do a cleanup
        $this->cleanup();

        $this->pingOthers();

        // then search for finished tickets for a last backup
        if (($ticket = $this->finished()) !== null) {
            if ($this->lockItem($ticket)) {
                $this->finishBackup = true;
                return $ticket;
            }
        }

        /*
         * Now those which weren't tried in the last n seconds (n=backup_interval)
         */
        $query = $this->queryNotAbandoned()
            ->andWhere(['not', ['start' => null]])
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
                ['backup_last_try' => null],
                [
                    'and',
                    [
                        '<',
                        new Expression('unix_timestamp(`backup_last`)'),
                        new Expression('unix_timestamp(`backup_last_try`) - 5')
                    ],
                    ['<', 'backup_last_try', new Expression('NOW() - INTERVAL 1 MINUTE')],
                ]
            ])
            ->andWhere(['backup_lock' => 0])
            ->andWhere(['restore_lock' => 0])
            ->andWhere(['bootup_lock' => 0])
            ->orderBy(new Expression('unix_timestamp(`backup_last_try`) + `backup_interval` ASC'));

        // finally lock the next ticket and return it
        if (($ticket = $query->one()) !== null) {
            if ($this->lockItem($ticket)) {
                return $ticket;
            }
        }

        return null;

    }

    /**
     * Returns the query for a ticket which is not abandoned.
     *
     * @see Ticket::getAbandoned()
     * 
     * @return yii\db\Query
     */
    public function queryNotAbandoned ()
    {
        $at = \Yii::$app->params['abandonTicket'] === null ? 'NULL' : \Yii::$app->params['abandonTicket'];
        return Ticket::find()
            ->joinWith('exam')
            ->where([
                '>=',

                # the computed abandoned time (cat). Ticket is abandoned after this amount of seconds
                new Expression('COALESCE(NULLIF(`ticket`.`time_limit`,0),NULLIF(`exam`.`time_limit`,0),ABS(' . $at . '/60),180)*60'),

                # amount of time since last successful backup or since the exam has started and the last backup try or now (nbt)
                new Expression('COALESCE(unix_timestamp(`ticket`.`backup_last_try`), unix_timestamp(NOW())) - COALESCE(unix_timestamp(`ticket`.`backup_last`), unix_timestamp(`ticket`.`start`))')
            ])
            ->andWhere(['last_backup' => 0]);
    }

    /**
     * Get directory size recursively
     *
     * @param string $dir Path to the directoy
     * @return int Total size in bytes
     */
    private function directorySize ($dir)
    {
        $size = 0;
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') { continue; }
            $item = $dir . '/' . $item;

            unset($stat);
            $stat = @lstat($item);
            if (isset($stat['size']) && is_int($stat['size'])) {
                $size += $stat['size'];
            }

            if (is_dir($item)) {
                $size += $this->directorySize($item);
            }
        }
        return $size;
    }
}