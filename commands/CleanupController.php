<?php

namespace app\commands;

use yii;
use app\commands\DaemonController;
use app\models\DaemonInterface;
use yii\db\Expression;

/**
 * DB Cleaner
 *
 * Cleans up the database and other stuff
 */
class CleanupController extends DaemonController implements DaemonInterface
{

    /**
     * @var int interval in which the db should be cleaned (in seconds)
     */    
    public $cleanInterval = 300;

    /**
     * @var int timestamp when the db was cleaned the last time 
     */    
    private $dbcleaned = null;

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
     * Delete all event items older than 3600 seconds and all event streams older than 1 day
     */
    public function processItem ($item = null)
    {
        $cleanEvents = \Yii::$app->db
            ->createCommand()
            ->delete('event', ['<', 'generated_at', microtime(true) - 3600]);
        $cleanEvents->execute();

        $cleanEventStreams = \Yii::$app->db
            ->createCommand()
            ->delete('event_stream', ['<', 'started_at', new Expression('NOW() - INTERVAL 1 DAY')]);
        $cleanEventStreams->execute();

        $this->dbcleaned = microtime(true);
        $this->logInfo('Database cleaned.', true, false, true);

        // remove event payload files that are older than 60 seconds
        if (is_writable(\Yii::$app->params['tmpPath'])) {
            foreach (glob(\Yii::$app->params['tmpPath'] . "/event-*") as $file) {
                if (filemtime($file) + 60 < microtime(true)) {
                    @unlink($file);
                }
            }
        }

        $this->logInfo('tmpPath cleaned.', true, false, true);
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
        if ($this->dbcleaned < microtime(true) - $this->cleanInterval){
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
