<?php

namespace app\commands;

use yii;
use \yii\db\ActiveRecord;
use yii\helpers\StringHelper;
use app\commands\DaemonController;
use app\models\Ticket;
use app\models\BackupFile;
use app\models\RdiffFileSystem;

/**
 * Index to Elasticsearch
 * This is the process which indexes all specified tables to elasticsearch
 */
class IndexController extends DaemonController
{

    public $list = [
        #'howto'   => 'app\models\Howto',
        #'user'    => 'app\models\User',
        #'exam'    => 'app\models\Exam',
        #'ticket'  => 'app\models\Ticket',
        #'restore' => 'app\models\Restore',
        #'backup'  => 'app\models\Backup',
        #'file'    => 'app\models\RdiffFileSystem',
        'file'    => 'app\models\file\ZipFile',
        'file'    => 'app\models\file\SquashfsFile',
        'file'    => 'app\models\file\FileInArchive',
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

            /* recreate all indices */
            foreach ($this->list as $index => $class) {
                $model = new $class();
                foreach ($model->behaviors() as $name => $config) {
                    if ($config['class'] == "app\components\ElasticsearchBehavior") {
                        $this->recreateIndex($class, $name);
                    }
                }
            }

            /* fill the indices */
            foreach ($this->list as $index => $class) {
                $model = new $class();
                foreach ($model->behaviors() as $name => $config) {
                    if ($config['class'] == "app\components\ElasticsearchBehavior") {
                        $model = new $class();
                        $behavior = $model->getBehavior($name);
                        if (is_callable($behavior->allModels)) {
                            $models = call_user_func($behavior->allModels, $class);
                            $this->insertAllModels($models, $name);
                        } elseif (is_array($behavior->allModels)) {
                            foreach (call_user_func($behavior->allModels['foreach'], $class) as $outerModel) {
                                if (is_callable($behavior->allModels['allModels'])) {
                                    $models = call_user_func($behavior->allModels['allModels'], $outerModel);
                                    $this->insertAllModels($models, $name);
                                } elseif (is_array($behavior->allModels['allModels'])) {
                                    foreach (call_user_func($behavior->allModels['allModels']['foreach'], $outerModel) as $innerModel) {
                                        $models = call_user_func($behavior->allModels['allModels']['allModels'], $innerModel);
                                        $this->insertAllModels($models, $name);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $this->unlock('index');
        }
    }

    public function recreateIndex($class, $name)
    {
        $model = new $class();
        $behavior = $model->getBehavior($name);
        if ($behavior->index !== false) {
            $this->logInfo('deleting index ' . $behavior->index);
            $behavior->deleteIndex();
            $this->logInfo('creating index ' . $behavior->index);
            $behavior->createIndex();
        }
    }

    public function insertAllModels($models, $name)
    {
        if (!empty($models)) {
            $behavior = $models[0]->getBehavior($name);
            $n = count($models);
            $this->logInfo('inserting ' . $n . ' documents to index ' . $behavior->index);

            $i = 0;
            $event = new yii\db\AfterSaveEvent();
            foreach ($models as $model) {
                $behavior = $model->getBehavior($name);
                $r = $behavior->insertDocument($event);
                $i += intval($r);
            }
            $this->logInfo('inserted ' . $i . '/' . $n . ' documents to index ' . $behavior->index);
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
