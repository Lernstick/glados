<?php

namespace app\commands;

use yii;
use app\commands\DaemonController;
use app\models\DaemonInterface;

/**
 * DB Clean Daemon
 * Cleans up the database
 */
class DbcleanController extends DaemonController implements DaemonInterface
{


    /**
     * @var int timestamp when the db was cleaned the last time 
     */    
    public $dbcleaned = null;

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
     * Delete all event items older than 3600 seconds
     */
    public function processItem ($item = null)
    {
        $now = microtime(true);
        $timestamp = $now - 3600;

        $this->logInfo('Cleaning database...', true, false, true);

        $q = \Yii::$app->db
            ->createCommand()
            ->delete('event', ['<', 'generated_at', $timestamp]);
        $q->execute();
        $this->dbcleaned = $now;
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
        if ($this->dbcleaned < microtime(true) - 300){
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
