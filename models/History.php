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
 *
 * @property User $user
 * @property string $userName
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'changed_by']);
    }

    /**
     * Getter for user name
     *
     * @return string The user name or "System" if user id is 0 or "(user removed)"
     * if the user does not exist (anymore).
     */
    public function getUserName()
    {
        if ($this->changed_by == 0) {
            return \Yii::t('history', 'System');
        } else if ($this->changed_by == -1) {
            return '<span class="not-set">' . \Yii::t('history', '(unknown user)') . '</span>';
        } else if ($this->user == null) {
            return '<span class="not-set">' . \Yii::t('history', '(user removed)') . '</span>';
        } else {
            return $this->user->username;
        }
    }

}
