<?php

namespace app\models;

use Yii;
use yii\helpers\FileHelper;
use app\models\EventStream;

/**
 * This is the model class for apaches mod_status.
 *
 */
class ServerStatus extends \yii\db\ActiveRecord
{

    /**
     * @const string the url under which the server status can be found
     */
    const URL = 'http://localhost/server-status?auto';

    /**
     * @const string Path to /proc/meminfo
     */
    const PROC_MEMINFO = '/proc/meminfo';

    /**
     * @const string Path to /proc/stat
     */
    const PROC_STAT = '/proc/stat';

    /**
     * @var string the raw output from URL
     */
    public $raw;

    /**
     * @var integer update interval in seconds
     */
    public $interval = 10;

    /**
     * Webserver variables
     * @see http://httpd.apache.org/docs/2.0/mod/mod_status.html
     */
    public $serverVersion;
    public $serverUptimeSeconds;
    public $load1;
    public $load5;
    public $load15;
    public $uptime;
    public $busyWorkers;
    public $idleWorkers;
    public $scoreboard;
    public $reqPerSec;

    public $procWaiting = 0;
    public $procStarting = 0;
    public $procReading = 0;
    public $procSending = 0;
    public $procKeepalive = 0;
    public $procDNS = 0;
    public $procClosing = 0;
    public $procLogging = 0;
    public $procFinishing = 0;
    public $procIdle = 0;
    public $procOpen = 0;
    public $procTotal = 0;
    public $procMaximum = 0;

    /**
     * Server memory variables variables
     * These values are converted from kB to bytes
     * @see https://www.kernel.org/doc/Documentation/filesystems/proc.txt
     */
    public $memTotal = 0;
    public $memFree = 0;
    public $memAvailable = 0;
    public $memUsed = 0;
    public $swapTotal = 0;
    public $swapFree = 0;
    public $swapUsed = 0;

    /**
     * CPU variables
     */
    public $cpuPercentage;

    /**
     * Disk variables
     */
    public $diskTotal = [];
    public $diskUsed = [];
    public $diskName = [];
    public $diskPath = [];

    /**
     * Inotify variables
     */
    public $inotify_max_user_instances;
    public $inotify_max_user_watches;
    public $inotify_active_watches;
    public $inotify_active_instances;

    /**
     * @var integer Maximal allowed number of connections to the database, if exhausted
     * we see the "to many connections" error.
     */
    public $db_max_connections;

    /**
     * @var integer number of currently active connections to the database.
     */
    public $db_threads_connected;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['interval', 'safe'],
            ['interval', 'required'],
            ['interval', 'default', 'value' => 10],
            ['interval', 'integer', 'min' => 1, 'max' => 300],
        ];
    }

    /**
     * @inheritdoc
     */
    public function formName()
    {
        /**
         * no more cluttered URL: index.php?ServerStatus[interval]=10
         * instead: index.php?interval=10
         */
        return '';
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            'interval' => \Yii::t('server', 'Update Interval')
        ];
    }


    /**
     * @return array customized attribute labels
     */
    public function attributeHints()
    {
        return [
            'interval' => \Yii::t('server', 'seconds')
        ];
    }

    /**
     * Return the Screeshot model related to the token and the date
     *
     * @return ServerStatus|null
     */
    public static function find()
    {
        $status = new ServerStatus();

        // read out all information from the ServerStatus URL
        $status->raw = @file_get_contents(ServerStatus::URL);
        foreach(explode(PHP_EOL, $status->raw) as $line) {
            if (str_contains($line, ":")) {
                list($key, $value) = explode(":", $line, 2);
                if ($status->hasProperty(lcfirst($key))) {
                    $status->{lcfirst($key)} = trim($value);
                }
            }
        }
        $status->readScoreboard();

        /**
         * inotify information
         * @todo read current inotify resouces
         */
        $status->inotify_max_user_instances = intval(file_get_contents('/proc/sys/fs/inotify/max_user_instances'));
        $status->inotify_max_user_watches = intval(file_get_contents('/proc/sys/fs/inotify/max_user_watches'));
        $status->inotify_active_instances = 0;
        $status->inotify_active_watches = 0;
        $streams = EventStream::find()->all();
        foreach ($streams as $stream) {
            if ($stream->isActive) {
                $status->inotify_active_instances++;
                $status->inotify_active_watches += $stream->watches;
            }
        }


        /**
         * MariaDB information
         * @var \yii\db\ActiveQuery $query
         */
        $query = Yii::createObject(\yii\db\ActiveQuery::className(), [get_called_class()]);
        $status->db_max_connections = $query->select('VARIABLE_VALUE')->from('information_schema.GLOBAL_VARIABLES')->where(['VARIABLE_NAME' => 'max_connections'])->scalar();
        $status->db_threads_connected = $query->select('VARIABLE_VALUE')->from('information_schema.GLOBAL_STATUS')->where(['VARIABLE_NAME' => 'threads_connected'])->scalar();


        /**
         * Memory information
         */
        $raw = @file_get_contents(ServerStatus::PROC_MEMINFO);
        foreach(explode(PHP_EOL, $raw) as $line) {
            if (str_contains($line, ":")) {
                list($key, $value) = explode(":", $line, 2);
                if ($status->hasProperty(lcfirst($key))) {
                    $status->{lcfirst($key)} = intval($value)*1024;
                }
            }
        }
        $status->memUsed = $status->memTotal - $status->memAvailable;
        $status->swapUsed = $status->swapTotal - $status->swapFree;

        /**
         * CPU information
         */
        list($used1, $total1) = $status->readStat();
        usleep(1000*100); # 0.1s
        list($used2, $total2) = $status->readStat();
        $status->cpuPercentage = ($used2 - $used1)*100/($total2 - $total1);

        /**
         * Disk information
         */
        $paths = [
            'root' => '/',
            'backup' => \Yii::$app->params['backupPath'],
            'screencapture' => \Yii::$app->params['scPath'],
            'upload' => \Yii::$app->params['uploadPath'],
            'result' => \Yii::$app->params['resultPath'],
            'tmp' => \Yii::$app->params['tmpPath'],
        ];
        $p = [];
        foreach($paths as $name => $path) {
            $dev = stat($path)[0];
            if (!array_key_exists($dev, $status->diskTotal)) {
                $status->diskTotal[$dev] = disk_total_space($path);
                $status->diskUsed[$dev] = $status->diskTotal[$dev] - disk_free_space($path);
                $base = '';
                while ($dev == stat($path)[0] && $path != '/') {
                    $base = basename($path);
                    $path = dirname($path);
                }
                $p = FileHelper::normalizePath(join(DIRECTORY_SEPARATOR, array($path, $base)));
                $status->diskPath[$dev] = empty($p) ? '/' : $p;

            }
            $status->diskName[$dev][] = $name;
        }

        return $status;
    }

    /**
     * Reads the cpu part of the file /proc/stat
     *
     * @return array [$used, $total]
     */
    private function readStat()
    {
        $raw = @file_get_contents(ServerStatus::PROC_STAT);
        foreach(explode(PHP_EOL, $raw) as $line) {
            if (str_contains($line, "cpu ")) {
                list($key, $user, $nice, $system, $idle, $iowait, $irq, $softirq, $rest) = preg_split('/\s+/', $line, 9);
                return [$user + $system, $user + $system + $idle];
            }
        }
    }

    /**
     * Propagates the scoreboard quantities
     *
     * @return void
     */
    private function readScoreboard()
    {
        foreach (str_split($this->scoreboard) as $char) {
            switch ($char) {
                case "_": # Waiting for Connection
                    $this->procWaiting++;
                    $this->procTotal++;
                    break;
                case "S": # Starting up
                    $this->procStarting++;
                    $this->procTotal++;
                    break;
                case "R": # Reading Request
                    $this->procReading++;
                    $this->procTotal++;
                    break;
                case "W": # Sending Reply
                    $this->procSending++;
                    $this->procTotal++;
                    break;
                case "K": # Keepalive (read)
                    $this->procKeepalive++;
                    $this->procTotal++;
                    break;
                case "D": # DNS Lookup
                    $this->procDNS++;
                    $this->procTotal++;
                    break;
                case "C": # Closing connection
                    $this->procClosing++;
                    $this->procTotal++;
                    break;
                case "L": # Logging
                    $this->procLogging++;
                    $this->procTotal++;
                    break;
                case "G": # Gracefully finishing
                    $this->procFinishing++;
                    $this->procTotal++;
                    break;
                case "I": # Idle cleanup of worker
                    $this->procIdle++;
                    $this->procTotal++;
                    break;
                case ".": # Open slot with no current process
                    $this->procOpen++;
                    break;
                default:
            }
            $this->procMaximum++;
        }
    }

}
