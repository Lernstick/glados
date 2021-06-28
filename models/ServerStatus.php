<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\Url;
use yii\imagine\Image;

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
     * @var string the raw output from URL
     */
    public $raw;

    /**
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

    public $inotify_max_user_instances;
    public $inotify_max_user_watches;

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [];
    }

    /**
     * Return the Screeshot model related to the token and the date
     *
     * @return ServerStatus|null
     */
    public function find()
    {
        $status = new ServerStatus();
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

        $status->inotify_max_user_instances = intval(file_get_contents('/proc/sys/fs/inotify/max_user_instances'));
        $status->inotify_max_user_watches = intval(file_get_contents('/proc/sys/fs/inotify/max_user_watches'));

        return $status;
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
