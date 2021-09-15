<?php

namespace app\commands;

use yii;
use app\commands\DaemonController;
use app\models\Ticket;
use app\models\Daemon;
use app\models\Activity;

/**
 * Network Process
 *
 * This class contains useful methods for daemons that perform network operations
 * on tickets/clients.
 */
class NetworkController extends DaemonController
{

    /**
     * @var Ticket The ticket in processing at the moment 
     */
    public $ticket;

    /**
     * @var string the type of lock used in this daemon, eg: backup, download, restore, ...
     */
    public $lock_type;

    /**
     * @var string the name of the property holding the lock
     */
    public $lock_property;

    /**
     * @var string date of the current logfile
     */
    public $logfileDate;

    /**
     * @var string|null the constructed path of the logfile
     */
    public function getLogfile()
    {
        return $this->logfileDate === null ? null : substitute('{path}/{type}.{token}.{date}.log', [
            'path' => Yii::getAlias('@runtime/logs'),
            'type' => $this->lock_type,
            'token' => $this->ticket->token,
            'date' => $this->logfileDate,
        ]);
    }

    /**
     * Determines if a given port on the target system is open or not
     *
     * @param integer $port The port to check
     * @param integer $tries The number to times to try (with 0.1 seconds delay inbetween every check)
     * @param string &$errstr contains the error message of the last try from fsockopen()
     * @param string &$errno the error code of the last try from connect()
     * @param float $timeout total timeout in seconds for all attempts
     * @return boolean Whether the port is open or not
     */
    public function checkPort ($port, $tries = 1, &$errstr = null, &$errno = null, $timeout = 30)
    {
        for ($c=1;$c<=$tries;$c++) {
            $fp = @fsockopen($this->ticket->ip, $port, $errno, $errstr, (float) $timeout/$tries);
            if (!$fp) {
                // port is closed or blocked
                $error = substitute('Port {port} is closed to blocked on ticket with token {token} and ip {ip}, error code: {code}, error message: {error}. (try {i}/{n})', [
                    'port' => $port,
                    'token' => $this->ticket->token,
                    'ip' => $this->ticket->ip,
                    'code' => $errno,
                    'error' => $errstr,
                    'i' => $c,
                    'n' => $tries,
                ]);
                file_put_contents($this->logfile, $error . PHP_EOL, FILE_APPEND);
                $this->logError($error);
                sleep(0.1);
            } else {
                // port is open and available
                fclose($fp);
                return true;
            }
        }
        return false;
    }

    /**
     * Clean up locked tickets. If a ticket stays in backup_lock or restore_lock and
     * its associated daemon is not running anymore, this function will unlock those tickets.
     *
     * @return void
     */
    public function cleanup ()
    {
        $query = Ticket::find()
            ->where(['backup_lock' => 1])
            ->orWhere(['restore_lock' => 1])
            ->orWhere(['download_lock' => 1]);

        $tickets = $query->all();
        foreach ($tickets as $ticket) {
            if (($daemon = Daemon::findOne($ticket->running_daemon_id)) !== null) {
                if ($daemon->running != true) {
                    $this->logInfo("Unlocking item (token=" . $ticket->token . ")...");
                    $ticket->backup_lock = 0;
                    $ticket->restore_lock = 0;
                    $ticket->download_lock = 0;
                    $ticket->save(false);
                }
            } else {
                $ticket->backup_lock = 0;
                $ticket->restore_lock = 0;
                $ticket->download_lock = 0;
                $this->logInfo("Unlocking item (token=" . $ticket->token . ")...");
                $ticket->save(false);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function lockItem ($item)
    {
        if ($this->lock($item->id . "_" . $this->lock_type)) {
            $this->logfileDate = date('c');
            if ($item->hasProperty($this->lock_property)) {
                $item->{$this->lock_property} = 1;
            }
            if ($item->hasProperty('running_daemon_id')) {
                $item->running_daemon_id = $this->daemon->id;
            }
            return $item->save();
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function unlockItem ($item)
    {
        if ($this->unlock($item->id . "_" . $this->lock_type)) {
            $this->logfileDate = null;
        }

        if ($item->hasProperty($this->lock_property)) {
            $item->{$this->lock_property} = 0;
            return $item->save(false);
        }
        return true;
    }
}
