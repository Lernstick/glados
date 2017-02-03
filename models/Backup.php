<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\Ticket;
use app\models\RdiffFileSystem;

/**
 * This is the model class for the backup directory.
 *
 * @property string $date
 * @property string $token
 * @property integer $startTime
 * @property integer $endTime
 * @property integer $elapsedTime
 * @property string $sourceFiles
 * @property integer $sourceFileSize
 * @property string $mirrorFiles
 * @property integer $mirrorFileSize
 * @property string $newFiles
 * @property integer $newFileSize
 * @property string $deletedFiles
 * @property integer $deletedFileSize
 * @property string $changedFiles
 * @property integer $changedSourceSize
 * @property integer $changedMirrorSize
 * @property string $incrementFiles
 * @property integer $incrementFileSize
 * @property integer $totalDestinationSizeChange
 * @property integer $errors
 * @property array $errorLog
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
            'date' => 'Date',
            'token' => 'Token',
        ];
    }

    /**
     * @return Ticket
     */
    public function getTicket()
    {
        return Ticket::findOne(['token' => $this->token]);
    }

    public function getDir()
    {
        return \Yii::$app->params['backupDir'] . '/' . $this->token . '/rdiff-backup-data/';
    }

    /**
     * Getter for the errorLog
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
     * Returns all Backup models related to the token
     *
     * @param string $token - the token
     * @return Backup[]
     */
    public function findAll($token)
    {
        $dir = \Yii::$app->params['backupDir'] . '/' . $token . '/rdiff-backup-data/';
        $models = [];

        if (file_exists($dir)) {
            $files = scandir($dir, SCANDIR_SORT_DESCENDING);
            foreach ($files as $file) {
                if(preg_match('/^session_statistics\.(.*)\.data$/', $file, $matches)) {
                    if (isset($matches[1])) {
                        $models[] = Backup::findOne($token, $matches[1]);
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
        $file = \Yii::$app->params['backupDir'] . '/' . $token . '/'
             . 'rdiff-backup-data/session_statistics.' . $date . '.data';

        if(Yii::$app->file->set($file)->exists === false){
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
