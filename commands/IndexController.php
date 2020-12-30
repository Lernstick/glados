<?php

namespace app\commands;

use yii;
use \yii\db\ActiveRecord;
use app\commands\DaemonController;
use app\models\Ticket;

/**
 * Index to Elasticsearch
 * This is the process which indexes all specified tables to elasticsearch
 */
class IndexController extends DaemonController
{

    public $list = [
        'user' => 'app\models\User',
        'exam' => 'app\models\Exam',
        'ticket' => 'app\models\Ticket',
        'restore' => 'app\models\Restore',
    ];

    /**
     * @inheritdoc
     */
    public function start ()
    {
        parent::start();
    }

    /**
     * Restores files.
     *
     * @var array $args array of arguments
     * 
     * @inheritdoc
     */
    public function doJob ()
    {
        if ($this->lock('index')) {
            foreach ($this->list as $index => $class) {
                $this->recreateIndex($class);

                $models = $class::find()->all();
                $this->insertAllModels($models);
            }

            // backups
            $this->recreateIndex('app\models\Backup');

            $tickets = Ticket::find()->all();
            foreach ($tickets as $ticket) {
                if ($ticket->backup) {
                    $this->insertAllModels($ticket->backups);
                }
            }

            $this->unlock('index');
        }
    }

    public function recreateIndex($class)
    {
        $model = new $class();
        $behavior = $model->getBehavior('ElasticsearchBehavior');
        $behavior->deleteIndex();
        $behavior->createIndex();
    }

    public function insertAllModels($models)
    {
        $event = new yii\db\AfterSaveEvent();
        foreach ($models as $model) {
            $behavior = $model->getBehavior('ElasticsearchBehavior');
            $behavior->insertDocument($event);
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
