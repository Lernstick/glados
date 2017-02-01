<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\Url;
use yii\imagine\Image;

class Screenshot extends Model
{
    public $date;
    public $token;
    public $path;
    private $_thumbnail;

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
        ];
    }

    public function getSrc()
    {
        return Url::to(['screenshot/view', 'date' => $this->date, 'token' => $this->token]);
    }

    public function getTsrc()
    {
        return Url::to(['screenshot/thumbnail', 'date' => $this->date, 'token' => $this->token]);
    }

    public function findOne($token, $date)
    {
        //$dir = \Yii::$app->params['backupDir'] . '/' . $token . '/Screenshots/';
        $dir = \Yii::getAlias('@app') . '/backups/' . $token . '/Screenshots/';
        if (file_exists($dir)) {
            $files = scandir($dir, SCANDIR_SORT_DESCENDING);
            foreach ($files as $screenshot) {
                $path = $dir . '/' . $screenshot;
                if(@is_array(getimagesize($path)) && filemtime($path) == $date){
                    return new Screenshot([
                        'path' => $path,
                        'token' => $token,
                        'date' => $date,
                    ]);
                }
            }
        }

        return null;

    }

    public function findAll($token)
    {

        $dir = \Yii::getAlias('@app') . '/backups/' . $token . '/Screenshots/';
        $models = [];

        if (file_exists($dir)) {
            $files = scandir($dir, SCANDIR_SORT_DESCENDING);
            foreach ($files as $screenshot) {
                $path = $dir . '/' . $screenshot;
                if(@is_array(getimagesize($path))){
                    $models[] = new Screenshot([
                        'path' => $path,
                        'token' => $token,
                        'date' => filemtime($path),
                    ]);
                }
            }
        }
        return $models;
    }

    /**
     * Generates a thumbnail of the screenshot if not yet created.
     * 
     * @return string - the path to the generated thumbnail
     */
    public function getThumbnail()
    {
        $dir = Yii::getAlias('@runtime/thumbnails/' . $this->token);
        $tpath = $dir . '/' . basename($this->path);
        if (!file_exists($tpath)) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }            
            Image::thumbnail($this->path, 250, null)
                ->save($tpath, ['quality' => 50]);
        }
        return $tpath;
    }

}
