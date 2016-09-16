<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\Url;

class Screenshot extends Model
{
    public $date;
    public $token;
    public $path;

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

    public function findOne($token, $date)
    {
        $dir = \Yii::$app->params['backupDir'] . '/' . $token . '/Screenshots/';
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
        $dir = \Yii::$app->params['backupDir'] . '/' . $token . '/Screenshots/';
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

}
