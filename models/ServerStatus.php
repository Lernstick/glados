<?php

namespace app\models;

use Yii;
use yii\helpers\FileHelper;
use app\models\EventStream;
use yii\base\Model;
use app\models\Base;

/**
 * This is the model class for apaches mod_status.
 *
 */
class ServerStatus extends Model
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
     * @const string Path to /proc/net/dev
     */
    const PROC_STAT = '/proc/stat';

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
    public $ioPercentage;
    public $ncpu;

    /**
     * Daemons
     */
    public $runningDaemons;
    public $averageLoad;

    /**
     * Disk variables
     */
    public $diskTotal = [];
    public $diskUsed = [];
    public $diskName = [];
    public $diskPath = [];

    /**
     * Network variables
     */
    public $netMaxSpeed = [];
    public $netCurrentSpeed = [];
    public $netName = [];

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
            'interval' => \Yii::t('server', 'Update Interval'),
            'procTotal' => \Yii::t('server', 'Webserver processes'),
            'db_threads_connected' => \Yii::t('server', 'Database connections'),
            'runningDaemons' => \Yii::t('server', 'Running daemons'),
            'averageLoad' => \Yii::t('server', 'Average daemon load'),
            'memTotal' => \Yii::t('server', 'Memory usage'),
            'swapTotal' => \Yii::t('server', 'Swap usage'),
            'cpuPercentage' => \Yii::t('server', 'CPU usage'),
            'ioPercentage' => \Yii::t('server', 'I/O usage'),
            'inotify_active_watches' => \Yii::t('server', 'Inotify watches'),
            'inotify_active_instances' => \Yii::t('server', 'Inotify instances'),
            'diskUsed' => \Yii::t('server', 'Disk usage'),
            'netUsage' => \Yii::t('server', 'Network usage'),
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
        $raw = @file_get_contents(ServerStatus::URL);
        foreach(explode(PHP_EOL, $raw) as $line) {
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
        $query = Base::find();
        $status->db_max_connections = intval($query->select('VARIABLE_VALUE')
            ->from('information_schema.GLOBAL_VARIABLES')
            ->where(['VARIABLE_NAME' => 'max_connections'])
            ->scalar());
        $status->db_threads_connected = intval($query->select('VARIABLE_VALUE')
            ->from('information_schema.GLOBAL_STATUS')
            ->where(['VARIABLE_NAME' => 'threads_connected'])
            ->scalar());

        /**
         * Daemons
         */
        $daemons = new DaemonSearch();
        $sum = Daemon::find()->where(['description' => 'Base Process'])->sum('`load`');
        $count = intval(Daemon::find()->where(['description' => 'Base Process'])->count());
        $status->runningDaemons = $daemons->search([])->totalCount;
        $status->averageLoad = $count != 0 ? round(100*$sum/$count) : 0;


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
         * CPU, I/O and Network information
         */
        list($cpu_used1, $cpu_iowait1, $cpu_total1) = $status->readStat();
        $cards = array_slice(scandir('/sys/class/net/'), 2);
        foreach ($cards as $card) {
            $max = intval(@file_get_contents(substitute('/sys/class/net/{dev}/speed', ['dev' => $card])));
            if ($max !== 0) {
                $status->netMaxSpeed[$card] = $max;
                $time[$card] = microtime(true);
                $rx[$card] = intval(@file_get_contents(substitute('/sys/class/net/{dev}/statistics/rx_bytes', ['dev' => $card])));
                $tx[$card] = intval(@file_get_contents(substitute('/sys/class/net/{dev}/statistics/tx_bytes', ['dev' => $card])));
            }
        }
        usleep(1000*100); # 0.1s
        list($cpu_used2, $cpu_iowait2, $cpu_total2) = $status->readStat();
        $status->cpuPercentage = ($cpu_used2 - $cpu_used1)*100/($cpu_total2 - $cpu_total1);
        $status->ioPercentage = ($cpu_iowait2 - $cpu_iowait1)*100/($cpu_total2 - $cpu_total1);
        foreach ($rx as $card => $val) {
            $crx = intval(@file_get_contents(substitute('/sys/class/net/{dev}/statistics/rx_bytes', ['dev' => $card])));
            $ctx = intval(@file_get_contents(substitute('/sys/class/net/{dev}/statistics/tx_bytes', ['dev' => $card])));
            $delta = microtime(true) - $time[$card]; # seconds
            $status->netName[$card] = $card;
            $status->netCurrentSpeed[$card] = (($crx - $rx[$card])/1048576)/$delta; # MBytes/sec
            $status->netCurrentSpeed[$card] += (($ctx - $tx[$card])/1048576)/$delta; # MBytes/sec
        }

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
        foreach ($paths as $name => $path) {
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
     * @return array [$cpu, $io, $total]
     */
    private function readStat()
    {
        $raw = @file_get_contents(ServerStatus::PROC_STAT);
        $this->ncpu = substr_count($raw, 'cpu') -1; # -1 because of the average CPU line
        foreach(explode(PHP_EOL, $raw) as $line) {
            if (str_contains($line, "cpu ")) { # only catch the average line
                list($key, $user, $nice, $system, $idle, $iowait, $irq, $softirq, $steal, $rest) = preg_split('/\s+/', $line, 10);
                $i = $idle + $iowait; # total idle
                $n = $user + $nice + $system + $irq + $softirq + $steal; # total non-idle
                $t = $i + $n; # total
                return [$t - $i, $iowait, $t];
            }
        }
    }

    /**
     * Reads the scoreboard quantities
     *
     * @return void
     */
    private function readScoreboard()
    {
        # reset the numbers
        $this->procTotal = 0;
        $this->procWaiting = 0;
        $this->procStarting = 0;
        $this->procReading = 0;
        $this->procSending = 0;
        $this->procKeepalive = 0;
        $this->procDNS = 0;
        $this->procClosing = 0;
        $this->procLogging = 0;
        $this->procFinishing = 0;
        $this->procIdle = 0;
        $this->procOpen = 0;
        $this->procMaximum = 0;

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

    /**
     * Generates the data attribute for the view
     *
     * @return array
     */
    public function getData()
    {

        $data = [
            'proc_total' => ['y' => $this->procTotal, 'max' => $this->procMaximum],
            'db_threads_connected' => ['y' => $this->db_threads_connected, 'max' => $this->db_max_connections],
            'running_daemons' => ['y' => $this->runningDaemons, 'max' => \Yii::$app->params['maxDaemons']],
            'average_load' => $this->averageLoad, # %
            'mem_used' => $this->memUsed/1048576, # MB
            'swap_used' => $this->swapUsed/1048576, # MB
            'cpu_percentage' => $this->cpuPercentage, # %
            'io_percentage' => $this->ioPercentage, # %
            'inotify_active_watches' => ['y' => $this->inotify_active_watches, 'max' => $this->inotify_max_user_watches],
            'inotify_active_instances' => ['y' => $this->inotify_active_instances, 'max' => $this->inotify_max_user_instances],
        ];

        foreach($this->diskTotal as $key => $disk) {
            $data['disk_usage_' . $key] = $this->diskUsed[$key]/1073741824;
        }

        foreach($this->netName as $key => $dev) {
            $data['net_usage_' . $key] = $this->netCurrentSpeed[$key];
        }

        return $data;
    }

    /**
     * Returns information about the operating system PHP is running on.
     * Equivalent to `uname -a`.
     * @see https://www.php.net/manual/en/function.php-uname.php
     * @return string
     */
    public function uname()
    {
        return php_uname();
    }

}