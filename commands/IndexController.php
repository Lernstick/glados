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
        //'user' => 'app\models\User',
        //'exam' => 'app\models\Exam',
        //'ticket' => 'app\models\Ticket',
        //'restore' => 'app\models\Restore',
        //'howto' => 'app\models\Howto',
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

                if (method_exists($class, 'find')) {
                    $models = $class::find()->all();
                } else {
                    $models = $class::findAll();
                }
                $this->insertAllModels($models);
            }

            // backups
            $this->recreateIndex('app\models\Backup');
            $this->recreateIndex('app\models\Backup', 'ElasticsearchBehaviorLog');
            //$this->recreateIndex('app\models\Backup', 'ElasticsearchBehaviorFile');
            $this->recreateIndex('app\models\BackupFile');

            $tickets = Ticket::find()->all();
            foreach ($tickets as $ticket) {

                $fs = new RdiffFileSystem([
                    'root' => $ticket->exam->backup_path,
                    'location' => \Yii::$app->params['backupPath'] . '/' . $ticket->token,
                    'restoreUser' => 'root',
                    'restoreHost' => $ticket->ip,
                ]);

                if (file_exists(\Yii::$app->params['backupPath'] . '/' . $ticket->token)) {
                    $versions = $fs->slash('/')->versions;
                } else {
                    $versions = [];
                }

                foreach ($versions as $date) {
                    $this->recursiveInsert($fs, $date, $ticket->id);
                }

                /*if ($ticket->backup) {
                    $this->insertAllModels($ticket->backups, 'ElasticsearchBehaviorLog');
                    $this->insertAllModels($ticket->backups);
                }*/
            }

            $this->unlock('index');
        }
    }

    public function recursiveInsert($fs, $date, $ticket_id, $path = '/')
    {
        $f = $fs->slash($path)->versionAt($date);
        if ($f->type == 'dir') {
            //var_dump(["dir", $f->path, $date]);
            foreach ($f->contents as $rd) {
                $this->recursiveInsert($fs, $date, $ticket_id, $rd->path);
            }
        } else if ($f->type == 'file' && $f->state == 'normal' && $this->fileMatches($f->path)) {

            try {
                $content = @$f->restore(true);
            } catch (\Throwable $e) { // For PHP 7
                $content = null;
            } catch (Exception $e) {
                $content = null;
            }

            $model = new BackupFile([
                'id' => $f->path . ':' . $date,
                'path' => $f->path,
                'content' => $content,
                'ticket_id' => $ticket_id,
                'date' => $date,
            ]);
            $event = new yii\db\AfterSaveEvent();
            $behavior = $model->getBehavior('ElasticsearchBehavior');
            $r = $behavior->insertDocument($event);
            var_dump($r, $model->id);
            //var_dump(["file", @$f->restore(true), $date]);
        }
    }

    public function fileMatches($path)
    {
        return StringHelper::endsWith($path, 'txt');
    }


    public function recreateIndex($class, $name = 'ElasticsearchBehavior')
    {
        $model = new $class();
        $behavior = $model->getBehavior($name);
        $behavior->deleteIndex();
        $behavior->createIndex();
    }

    public function insertAllModels($models, $name = 'ElasticsearchBehavior')
    {
        $event = new yii\db\AfterSaveEvent();
        foreach ($models as $model) {
            $behavior = $model->getBehavior($name);
            $r = $behavior->insertDocument($event);
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
