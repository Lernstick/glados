<?php

namespace app\commands;

use yii;
use app\commands\NetworkController;
use yii\db\Expression;
use app\models\Ticket;
use app\models\Activity;
use yii\helpers\Console;
use app\models\DaemonInterface;
use app\components\ShellCommand;
use app\models\Issue;

/**
 * Notifier
 *
 * This daemon notifies the user via activities if a ticket has no backup since
 * a long amount of time.
 */
class NotifyController extends NetworkController implements DaemonInterface
{

    /**
     * @inheritdoc
     */
    public $ticket;

    /**
     * @inheritdoc
     */
    public $lock_type = 'notify';

    /**
     * @inheritdoc
     */
    public $lock_property = 'notify_lock';

    /**
     * @var int interval in which the tickets should be checked (in seconds)
     */    
    public $checkInterval = 60;

    /**
     * @var int timestamp when the tickets were checked the last time 
     */    
    private $checked = null;

    /**
     * @inheritdoc
     */
    public function doJobOnce ($id = '')
    {
        $this->processItem();
    }

    /**
     * @inheritdoc
     */
    public function doJob ($id = '')
    {
        $this->calcLoad(0);
        while (true) {
            $this->calcLoad(0);

            if ($id != '') {
                if (($ticket = Ticket::findOne($id)) !== null) {
                    $this->ticket = $ticket;

                    $this->ticket->client_state = 'Connecting to {ip} ...';
                    $this->ticket->client_state_params = ['ip' => $this->ticket->ip];
                    $this->ticket->save(false);
                    list($output, $online) = $this->pingCheck($this->ticket);
                    $this->ticket->refresh();
                    if ($online === true) {
                        $this->ticket->client_state = 'Client {ip} is online.';
                        $this->ticket->client_state_params = ['ip' => $this->ticket->ip];
                        $this->ticket->save(false);
                    } else {
                        $this->ticket->client_state = 'network error: {output}.';
                        $this->ticket->client_state_params = ['output' => $output];
                        $this->ticket->save(false);
                    }

                    $this->ticket = null;
                }
                return;
            }

            if ($this->getNextItem()){
                $this->processItem();
                $this->calcLoad(1);
            }

            pcntl_signal_dispatch();
            sleep(rand(5, 10));
            $this->calcLoad(0);
        }
    }

    /**
     * @inheritdoc
     */
    public function processItem ($item = null)
    {
        $this->processItemBackup($item); # TODO: same for backup as for ping
        $this->processItemPing($item);
    }

    /**
     * TODO
     */
    public function processItemPing ($item = null)
    {
        // Query tickets that are in regular running state
        $query = Ticket::find()
            // ticket should be in running state
            ->andWhere(['not', ['start' => null]])
            ->andWhere(['end' => null])
            ->andWhere(['not', ['ip' => null]])
            // bootup_lock should be gone, device succesfully booted
            ->andWhere(['bootup_lock' => 0]);

        $tickets = $query->all();
        foreach ($tickets as $ticket) {
            $this->ticket = $ticket;
            if ($this->lockItem($this->ticket) && !$this->ticket->abandoned) {
                $this->pingCheck($this->ticket);
                $this->unlockItem($this->ticket);
            }
        }
        $this->ticket = null;
    }

    /**
     * @param $ticket Ticket
     * @return array 
     */
    private function pingCheck($ticket)
    {
        list($output, $retval, $cmd) = $ticket->runCommand('true', 'C', 10);
        $online = $retval == 0 ? true : false;
        $ticket->online = $online;
        $ticket->save(false);

        if ($online) {
            Issue::markAsSolved(Issue::CLIENT_OFFLINE, $ticket->id);
        } else {
            Issue::markAs(Issue::CLIENT_OFFLINE, $ticket->id);
        }
        return [$output, $online];
    }

    /**
     * Notify all ticket, where the download has finished more
     * than 2 minutes ago, but not longer than one hour ago and
     * there was no message in the last 60 seconds and 
     * (backup_interval + 60 seconds) is over without a backup
     * being made.
     */
    public function processItemBackup ($item = null)
    {

        // Query tickets that had no backup for longer than the backup_interval time
        $query = Ticket::find()
            // ticket should be in running state
            ->andWhere(['not', ['start' => null]])
            ->andWhere(['end' => null])
            ->andWhere(['not', ['ip' => null]])
            // the backup should be enabled
            ->andWhere(['not', ['backup_interval' => 0]])
            // the download phase should be done already
            ->andWhere(['not', ['download_request' => null]])
            ->andWhere(['not', ['download_finished' => null]])
            ->andWhere([
                '>=',
                new Expression('unix_timestamp(`download_finished`)'),
                new Expression('unix_timestamp(`download_request`)')
            ])
            // the download should be finished for more than 2 minutes
            // but if it's longer than 3 hours, leave it
            ->andWhere([
                'between',
                'download_finished',
                new Expression('NOW() - INTERVAL 180 MINUTE'),
                new Expression('NOW() - INTERVAL 2 MINUTE')
            ]);

        $tickets = $query->all();
        foreach ($tickets as $ticket) {
            if ($this->lockItem($ticket) && !$ticket->abandoned) {

                $now = new \DateTime('now'); // now
                $then = new \DateTime('now -60 seconds');

                $query = Activity::find()->where(['ticket_id' => $ticket->id]);
                $query->andFilterWhere(['between', 'date', $then->format('Y-m-d H:i:s'), $now->format('Y-m-d H:i:s')]);
                $query->andFilterHaving(['description' => 'There was no successful backup since {n} minutes (the interval is set to {interval} minutes).']);

                // only trigger if there was no message in the last 60 seconds
                if ($query->count() == 0 /*&& count($ticket->backups) != 0*/) {
                    // only the dates are relevant
                    $dates = array_column($ticket->backups, 'date');
                    // sort the maximal date in index 0
                    usort($dates, function($a, $b) {
                        return strtotime($a) > strtotime($b) ? -1: 1;
                    });
                    #$last_backup_date = strtotime($dates[0]);
                    $last_backup_date = array_key_exists(0, $dates) ? strtotime($dates[0]) : strtotime($ticket->start);
                    $now = strtotime('now');
                    // only trigger if the (backup_interval + 60 seconds) is expired
                    if (abs($now - $last_backup_date) > intval($ticket->backup_interval) + 60) {

                        Issue::markAs(Issue::LONG_TIME_NO_BACKUP, $ticket->id);

                        $act = new Activity([
                                'ticket_id' => $ticket->id,
                                'description' => yiit('activity', 'There was no successful backup since {n} minutes (the interval is set to {interval} minutes).'),
                                'description_params' => [
                                    'n' => intval(abs($now - $last_backup_date)/60),
                                    'interval' => $ticket->backup_interval/60,
                                ],
                                'severity' => Activity::SEVERITY_ERROR,
                        ]);
                        $act->save();

                        $ticket->backup_state = 'There was no successful backup since {n} minutes (the interval is set to {interval} minutes).';
                        $ticket->backup_state_params = [
                            'n' => intval(abs($now - $last_backup_date)/60),
                            'interval' => $ticket->backup_interval/60,
                        ];
                        $ticket->save(false);

                        $this->logInfo(substitute('There was no successful backup of ticket with token {token} since {n} minutes (the interval is set to {interval} minutes).', [
                            'token' => $ticket->token,
                            'n' => intval(abs($now - $last_backup_date)/60),
                            'interval' => $ticket->backup_interval/60,
                        ]), true, true, true);

                    }
                }
                $this->unlockItem($ticket);
            }
        }

        $this->logInfo('Notify done.', true, false, true);
        $this->checked = microtime(true);
    }

    /**
     * @inheritdoc
     */
    public function getNextItem ()
    {
        if ($this->checked < microtime(true) - $this->checkInterval) {
            return true;
        } else {
            return false;
        }
    }
}