<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\Ticket;
use app\models\RdiffFileSystem;

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

    public function getTicket()
    {
        return Ticket::findOne(['token' => $this->token]);
    }

    public function getDir()
    {
        return \Yii::$app->params['backupDir'] . '/' . $this->token . '/rdiff-backup-data/';
    }

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

    public function browse($path)
    {

        $backup->filesystem = new RdiffFileSystem([
            'root' => '/home/user',
            'location' => $rootDir,
        ]);

        $backup->filesystem->slash('Desktop/file.txt')->current;

/*

        //restore file
        $backup->filesystem->slash('Desktop')->slash('file.txt')->versionAt('2016-06-01T12:41:44+02:00')->restore();
        //restore dir
        $backup->filesystem->slash('Desktop')->versionAt('2016-06-01T12:41:44+02:00')->restore();
        //restore all
        $backup->filesystem->slash()->versionAt('2016-06-01T12:41:44+02:00')->restore();        

        //Array of Objects of all increments of that file
        $backup->filesystem->slash('Desktop')->slash('file.txt')->increments;
        
        $backup->filesystem->slash(); //Obj of dir /home/user
        $backup->filesystem->slash('/'); //Obj of dir /home/user

        $backup->filesystem->slash('Desktop')->slash('file.txt')->path; // string "/home/user/Desktop/file.txt"
        $backup->filesystem->slash('Desktop')->slash('file.txt')->dirname; // /home/user/Desktop
        $backup->filesystem->slash('Desktop')->slash('file.txt')->basename; // file.txt
        $backup->filesystem->slash('Desktop')->slash('file.txt')->extenstion; //txt

        $backup->filesystem->slash('Desktop')->contents; //Array of Objects of all files and dirs in that dir
        $backup->filesystem->slash('Desktop/file.txt')->contents; //contents of the file
        $backup->filesystem->slash('Desktop/file.txt')->increments[1]->contents; //content of file (second latest version)
        $backup->filesystem->slash('Desktop/file.txt')->versionAt('2016-06-01T12:41:44+02:00')->contents;
        $backup->filesystem->slash('Desktop/file.txt')->restore(); // current version restore, also Obj->current->restore()

*/

        return true;

    }

    public function findFile($path)
    {
    }

}
