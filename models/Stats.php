<?php

namespace app\models;

use Yii;
use app\models\Base;
use yii\helpers\Html;

/**
 * This is the model class for table "stats".
 *
 * @property integer $id
 * @property string $key
 * @property string $value
 */
class Stats extends Base
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'stats';
    }

    /**
     * @inheritdoc 
     */
    public function rules()
    {
        return [
            [['key', 'value', 'type'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'key' => 'Key',
            'value' => 'Value',
            'type' => 'Datatype',
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'key' => 'Key',
            'value' => 'Value',
            'type' => 'Datatype',
        ];
    }

}
