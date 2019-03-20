<?php

namespace app\commands;

use yii;
use app\commands\DaemonController;
use yii\db\Expression;
use app\models\Ticket;
use app\models\Activity;
use yii\helpers\Console;
use app\models\DaemonInterface;

/**
 * Unlocker Daemon
 * This daemon unlocks tickets that are trapped in a locked state and will
 * not recover without an action. 
 * Tickts in the bootup_lock = 1 state, that somehow fail to give notice after they
 * successfully booted up, will be unlocked by this command.
 */
class UnlockController extends DaemonController implements DaemonInterface
{

    /**
     * @var int interval in which the tickets should be unlocked (in seconds)
     */    
    public $unlockInterval = 120;

    /**
     * @var int timestamp when the tickets were unlocked the last time 
     */    
    private $unlocked = null;

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
     *
     * Unlock all bootup_lock tickets, where the download has finished more
     * than 2 minutes ago, but not longer than one hour ago.
     */
    public function processItem ($item = null)
    {

        // Unlock blocked bootup_lock tickets
        $query = Ticket::find()
            ->where(['bootup_lock' => 1])
            // ticket should be in running state
            ->andWhere(['not', ['start' => null]])
            ->andWhere(['end' => null])
            ->andWhere(['not', ['ip' => null]])
            // the download phase should be done already
            ->andWhere(['not', ['download_request' => null]])
            ->andWhere(['not', ['download_finished' => null]])
            ->andWhere([
                '>=',
                new Expression('unix_timestamp(`download_finished`)'),
                new Expression('unix_timestamp(`download_request`)')
            ])
            // the download should be finished for more than 2 minutes
            // but if it's longer than 1 hour, leave it
            ->andWhere([
                'between',
                'download_finished',
                new Expression('NOW() - INTERVAL 60 MINUTE'),
                new Expression('NOW() - INTERVAL 2 MINUTE')
            ]);

        $tickets = $query->all();
        foreach ($tickets as $ticket) {
            if ($ticket->runCommand('[ -e /booted ] ', 'C', 10)[1] == 0) {
                $ticket->bootup_lock = 0;
                $ticket->save(false);

                $act = new Activity([
                        'ticket_id' => $ticket->id,
                        'description' => '"Bootup successful" message was not recieved, but client is successfully booted up. Client is now unlocked.',
                        'severity' => Activity::SEVERITY_NOTICE,
                ]);
                $act->save();  
            }
        }

        $this->logInfo('Tickets unclocked.', true, false, true);
        $this->unlocked = microtime(true);
    }

    /**
     * @inheritdoc
     */
    public function lockItem ($exam)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function unlockItem ($exam)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getNextItem ()
    {
        if ($this->unlocked < microtime(true) - $this->unlockInterval){
            return true;
        } else {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function stop($cause = null)
    {
        parent::stop($cause);
    }

}
