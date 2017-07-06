<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * This is the model class for the result directory.
 *
 */
class Result extends Model
{

    /**
     * @var UploadedFile
     */
    public $resultFile;
    public $filePath;
    public $file;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['file'], 'file', 'skipOnEmpty' => true, 'extensions' => 'zip', 'checkExtensionByMimeType' => true],        
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [];
    }

    /**
     * @return boolean
     */
    public function upload()
    {
        $this->filePath = \Yii::$app->params['resultPath'] . '/' . generate_uuid() . '.' . $this->file->extension;

        if ($this->validate(['file'])) {
            return $this->file->saveAs($this->filePath, true);
        } else {
            return false;
        }
    }


    /**
     * Return the Result model related to the hash
     *
     * @param string $hash - hash
     * @return Result
     */
    public function findOne($hash)
    {
        $file = \Yii::$app->params['resultPath'] . '/' . $hash . '.zip';

        if(Yii::$app->file->set($file)->exists === false){
            return null;
        }

        return new Result([
            'file' => $file,
        ]);
    }

}
