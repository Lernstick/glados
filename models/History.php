<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "history".
 *
 * @property integer $id
 * @property string $table
 * @property string $column
 * @property integer $row
 * @property timestamp $changed_at
 * @property integer $changed_by
 * @property string $old_value
 * @property string $new_value
 */
class History extends \yii\db\ActiveRecord
{


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'history';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => \Yii::t('history', 'ID'),
            'table' => \Yii::t('history', 'Table'),
            'column' => \Yii::t('history', 'Column'),
            'row' => \Yii::t('history', 'Row'),
            'changed_at' => \Yii::t('history', 'Changed At'),
            'changed_by' => \Yii::t('history', 'Changed By'),
            'old_value' => \Yii::t('history', 'Old Value'),
            'new_value' => \Yii::t('history', 'New Value'),
            'Hash' => \Yii::t('history', 'Hash'),
        ];
    }
}
