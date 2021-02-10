<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\FileHelper;
use app\models\Ticket;
use app\models\RdiffFileSystem;
use app\components\ElasticsearchBehavior;

/**
 * This is the model class for the backup directory.
 *
 * @property string $dir location of the rdiff-backup-data directory
 * @property array $errorLog contains the error log file line by line
 * @property array $backupLog contains the backup log file line by line
 *
 * @property Ticket $ticket
 */
class Backup extends Model
{
    public $date;
    public $token;
    public $startTime;
    public $endTime;
    public $elapsedTime;
    public $sourceFiles;
    public $sourceFileSize;
    public $mirrorFiles;
    public $mirrorFileSize;
    public $newFiles;
    public $newFileSize;
    public $deletedFiles;
    public $deletedFileSize;
    public $changedFiles;
    public $changedSourceSize;
    public $changedMirrorSize;
    public $incrementFiles;
    public $incrementFileSize;
    public $totalDestinationSizeChange;
    public $errors;

    public $id; // only used in commands/IndexController.php
    public $nr; // only used in commands/IndexController.php

    /**
     * @inheritdoc 
     */
    public function behaviors()
    {
        return [
            'Elasticsearch' => [
                'class' => ElasticsearchBehavior::className(),
                'index' => ['class' => '\app\models\indexes\BackupIndex'], // mappings are defined there
                'allModels' => [
                    'foreach' => function($class) { return Ticket::find()->all(); },
                    'allModels' => function($model) { return $model->backups; },
                ],
                // what the attributes mean
                'fields' => [
                    'date',
                    'errors',
                    'elapsedTime',
                    'sourceFiles',
                    'mirrorFiles',
                    'deletedFiles',
                    'changedFiles',
                    'incrementFiles',
                    'totalDestinationSizeChange',
                    'ticket' => function($m){ return $m->ticket->id; },
                ],
            ],
            'ElasticsearchErrorLog' => [
                'class' => ElasticsearchBehavior::className(),
                'index' => ['class' => '\app\models\indexes\LogIndex'], // mappings are defined there
                'id' => function($m){ return 'ticket:'.$m->ticket->id.'->backup:'.$m->nr.'->errorLog'; },
                'allModels' => [
                    'foreach' => function($class) { return Ticket::find()->all(); },
                    'allModels' => function($model) { return $model->backups; },
                ],
                // what the attributes mean
                'fields' => [
                    'logentries' => function($m){ return empty($m->errorLog) ? null : implode('', $m->errorLog); },
                    'backup' => function($m){ return $m->id; },
                    'ticket' => function($m){ return $m->ticket->id; },
                    'type' => function($m){ return 'error'; },
                ],
            ],
            'ElasticsearchBackupLog' => [
                'class' => ElasticsearchBehavior::className(),
                'index' => ['class' => '\app\models\indexes\LogIndex'], // mappings are defined there
                'id' => function($m){ return 'ticket:'.$m->ticket->id.'->backup:'.$m->nr.'->backupLog'; },
                'allModels' => [
                    'foreach' => function($class) { return Ticket::find()->all(); },
                    'allModels' => function($model) { return $model->backups; },
                ],
                // what the attributes mean
                'fields' => [
                    'logentries' => function($m){ return empty($m->backupLog) ? null : implode('', $m->backupLog); },
                    'backup' => function($m){ return $m->id; },
                    'ticket' => function($m){ return $m->ticket->id; },
                    'type' => function($m){ return 'info'; },
                ],
            ],
        ];
    }

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['date', 'token'], 'required'],
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            'date' => Yii::t('backups', 'Date'),
            'token' => Yii::t('backups', 'Token'),
            'elapsedTime' => Yii::t('backups', 'Elapsed Time'),
            'sourceFiles' => Yii::t('backups', 'Source Files'),
            'mirrorFiles' => Yii::t('backups', 'Mirror Files'),
            'deletedFiles' => Yii::t('backups', 'Deleted Files'),
            'changedFiles' => Yii::t('backups', 'Changed Files'),
            'incrementFiles' => Yii::t('backups', 'Increment Files'),
            'totalDestinationSizeChange' => Yii::t('backups', 'Total Destination Size Change'),
            'errors' => Yii::t('backups', 'Errors'),
        ];
    }

    /**
     * @return Ticket
     */
    public function getTicket()
    {
        return Ticket::findOne(['token' => $this->token]);
    }

    public function getBackupDir()
    {
        return \Yii::$app->params['backupPath'] . '/' . $this->token;
    }

    public function getDir()
    {
        return $this->backupDir . '/rdiff-backup-data/';
    }

    /**
     * Getter for the error log
     *
     * @return array
     */
    public function getErrorLog()
    {
        $errorLogFiles = [
            $this->dir . '/error_log.' . $this->date . '.data.gz',
            $this->dir . '/error_log.' . $this->date . '.data',
        ];

        foreach ($errorLogFiles as $file){
            if (file_exists($file)) {
                return gzfile($file);
            }
        }

        return [];
    }

    /**
     * Getter for the backup log
     *
     * @return array
     */
    public function getBackupLog()
    {
        $backupLogFiles = [
            $this->dir . '/backup.log',
        ];

        $me = new \DateTime($this->date);
        $current = false;
        $log = [];

        foreach ($backupLogFiles as $file){
            if (file_exists($file)) {
                $lines = array_reverse(gzfile($file));
                foreach ($lines as $line) {
                    if ($current) {
                        if (preg_match('/^--------------------------------------------------/', $line)) {
                            $current = false;
                            break;
                        }
                        array_unshift($log, $line);
                    }
                    if (preg_match('/^StartTime ([0-9\.]*) .*/', $line, $matches)) {
                        $d = new \DateTime();
                        $d->setTimestamp($matches[1]);
                        if ($d == $me) {
                            $current = true;
                        }
                    }
                }
            }
        }

        return array_filter($log, function($v) {
            return $v !== PHP_EOL && $v !== '--------------[ Session statistics ]--------------' . PHP_EOL;
        });
    }

    /**
     * Removes all backups
     *
     * @return void
     * @throws yii\base\ErrorException
     * @see https://www.yiiframework.com/doc/api/2.0/yii-helpers-basefilehelper#removeDirectory()-detail
     */
    public function delete()
    {
        return FileHelper::removeDirectory($this->backupDir);
    }

    /**
     * Returns all Backup models related to the token
     *
     * @param string $token - the token
     * @return Backup[]
     */
    public function findAll($token)
    {
        $ticket = Ticket::findOne(['token' => $token]);
        $dir = \Yii::$app->params['backupPath'] . '/' . $token . '/rdiff-backup-data/';
        $models = [];

        $i = 1;
        if (file_exists($dir)) {
            $files = scandir($dir, SCANDIR_SORT_DESCENDING);
            foreach ($files as $file) {
                if (preg_match('/^session_statistics\.(.*)\.data$/', $file, $matches)) {
                    if (isset($matches[1])) {
                        $model = Backup::findOne($token, $matches[1]);
                        $model->id = $ticket->id . ":" . $i;
                        $model->nr = $i;
                        $models[] = $model;
                        $i = $i+1;
                    }
                }
            }
        }

        return $models;
    }

    /**
     * Return the Backup model related to the token and the date
     *
     * @param string $token - token
     * @param string $date - date
     * @return Backup
     */
    public function findOne($token, $date)
    {
        $file = \Yii::$app->params['backupPath'] . '/' . $token . '/'
             . 'rdiff-backup-data/session_statistics.' . $date . '.data';

        if(file_exists($file) === false){            
            return null;
        }

        $me = new Backup;
        $contents = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($contents as $value) {
            list($att, $val) = explode(' ', $value);
            $att = lcfirst(\yii\helpers\Inflector::camelize($att));
            $me->$att = $val;
        }
        $me->date = $date;
        $me->token = $token;

        return $me;
    }

}
