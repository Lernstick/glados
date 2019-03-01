<?php

namespace app\commands;

use yii;
use app\commands\DaemonController;
use app\models\DaemonInterface;
use yii\db\Expression;
use app\models\Stats;
use app\models\StatsSearch;
use app\models\Ticket;
use yii\db\Query;

/**
 * Stats Daemon
 * Gathers statistics
 */
class StatsController extends DaemonController implements DaemonInterface
{

    private $now;
    private $_lastUpdated;

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
        $this->doJobOnce($id);
    }

    /**
     * @inheritdoc
     *
     * Get all statistics
     */
    public function processItem ($item = null)
    {

        $this->now = new \DateTime();
        echo $this->lastUpdated->format('U = Y-m-d H:i:s') . "\n";
        
        $then = $this->lastUpdated->setTime(0, 0);

        $i = 0;
        while($this->now >= $then) {
            $from = $then->format('Y-m-d');
            $to = $then->modify('+1 day')->format('Y-m-d');

            $this->updateItem('ticketsCompleted', $this->ticketsCompleted($from, $to), 'integer', $then->format('Y-m-d H:i:s'));
            $i += $this->ticketsCompleted($from, $to);

            echo $i . " --> " . $then->format('U = Y-m-d H:i:s') . "\n";

            $then = $then->modify('+1 day');
        }
        echo $i . "\n";
        echo $this->ticketsCompleted() . "\n";

        $from = $this->lastUpdated->format('Y-m-d');
        $to = $this->now->format('Y-m-d');
        if ($this->now > $this->lastUpdated) {
            $this->incrementItem('ticketsCompletedTotal', $this->ticketsCompleted($from, $to), 'integer', $this->now->format('Y-m-d H:i:s'));
        }


        $this->logInfo('Statistics updated.', true, false, true);
        $this->updateItem('statsUpdatedAt', microtime(true), 'timestamp', $this->lastUpdated->format('Y-m-d H:i:s'));
    }


    /**
     * Getter to determine when the statistics where last updated
     */
    public function getLastUpdated () {
        if (!isset($this->_lastupdated)) {
            $l = $this->getValue('statsUpdatedAt');
            
            // extract the installation time from miogration table
            if ($l == '0') {
                $query = new Query;
                $installTime = $query->select('apply_time')
                    ->from('migration')
                    ->where(['version' => 'm000000_000000_base'])
                    ->one();
                    
                $l = $installTime["apply_time"];
            }

            $this->_lastUpdated = new \DateTime();
            $this->_lastUpdated->setTimestamp($l);
            
        }
        return $this->_lastUpdated;
    }

    public function getValue ($key) {
        if (($model = Stats::find()->where(['key' => $key])->one()) !== null) {
            return $model->value;
        } else {
            return null;
        }
    }

    public function insertItem ($key, $value, $type, $date = null) {
        if (intval($value) != 0) {
            $model = new Stats([
                'date' => $date === null ? new \yii\db\Expression('NOW()') : $date,
                'key' => $key,
                'value' => $value,
                'type' => $type,
            ]);
            return $model->save();
        }
        return null;
    }

    public function updateItem ($key, $value, $type, $date = null) {
        if (($model = Stats::find()->where(['key' => $key, 'date' => $date])->one()) !== null) {
            $model->value = $value;
            $model->type = $type;
            //$model->date = new Expression('NOW()');
            return $model->save();
        } else {
            return $this->insertItem($key, $value, $type, $date);
        }
        
    }

    public function incrementItem ($key, $value, $type) {
        if (($model = Stats::find()->where(['key' => $key])->one()) !== null) {
            $model->value += $value;
            $model->type = $type;
            $model->date = new Expression('NOW()');
            return $model->save();
        } else {
            return $this->insertItem($key, $value, $type);
        }
    }

    public function ticketsCompleted ($from = null, $to = null)
    {
        $to = $to === null ? new Expression('NOW()') : $to;
        $query = Ticket::find()
            ->addSelect(['`ticket`.*', new \yii\db\Expression('TIMESTAMPDIFF(
                SECOND,
                `start`,
                `end`
            ) as diff')])
            ->having('diff <= :diff', [':diff' => 28800]); // only count exams shorter or equal than 8 hours

        $query = $from === null ? $query : $query->where(['between', 'end', $from, $to]);
        return $query->count();
    }

    /*
    ----------------------------------------------------------------------------------------------------
    */

    public function totalDuration ()
    {
        return Ticket::find()
            ->addSelect(['`ticket`.*', new \yii\db\Expression('TIMESTAMPDIFF(
                SECOND,
                `start`,
                `end`
            ) as diff')])
            ->having('diff <= :diff', [':diff' => 28800]) // only count exams shorter or equal than 8 hours
            ->sum('diff');
    }

    public function createdExams ()
    {
        return Exam::find()->count();
    }



    public function totalExams ()
    {
        return Ticket::find()->count();
    }

    public function examsCompletedToday ()
    {
        $start = new \DateTime('now');
        $end = new \DateTime('now +1 day');
        return Ticket::find()
            ->addSelect(['`ticket`.*', new \yii\db\Expression('TIMESTAMPDIFF(
                SECOND,
                `start`,
                `end`
            ) as diff')])
            ->having('diff <= :diff', [':diff' => 28800]) // only count exams shorter or equal than 8 hours
            ->where(['between', 'end', $start->format('Y-m-d'), $end->format('Y-m-d')])
            ->count();
    }



    /**
     * @inheritdoc
     */
    public function lockItem ($element)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function unlockItem ($element)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getNextItem ()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function stop($cause = null)
    {
        parent::stop($cause);
    }

}
