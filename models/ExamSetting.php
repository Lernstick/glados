<?php

namespace app\models;

use Yii;
use yii\helpers\Markdown;
use yii\helpers\ArrayHelper;
use app\models\ExamSettingAvail;

/**
 * This is the model class for table "exam_setting".
 *
 * @inheritdoc
 */
class ExamSetting extends Base
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'exam_setting';
    }


    /**
     * @inheritdoc 
     */
    public function rules()
    {
        return [
            [['value', 'key', 'belongs_to'], 'safe'],
            [['key'], 'required'],

            ['value', 'boolean', 'when' => function($m) { return $m->key == 'libre_autosave'; }],
            ['value', 'required', 'when' => function($m) { return $m->key == 'libre_autosave_path'; }],
            ['value', 'required', 'when' => function($m) { return $m->key == 'libre_autosave_interval'; }],
            ['value', 'integer', 'min' => 1, 'when' => function($m) { return $m->key == 'libre_autosave_interval'; }],
            ['value', 'boolean', 'when' => function($m) { return $m->key == 'libre_createbackup'; }],
            ['value', 'required', 'when' => function($m) { return $m->key == 'libre_createbackup_path'; }],
            ['value', 'boolean', 'when' => function($m) { return $m->key == 'screenshots'; }],
            ['value', 'required', 'when' => function($m) { return $m->key == 'screenshots_interval'; }],
            ['value', 'integer', 'min' => 1, 'max' => 100, 'when' => function($m) { return $m->key == 'screenshots_interval'; }],
            ['value', 'required', 'when' => function($m) { return $m->key == 'max_brightness'; }],
            ['value', 'integer', 'min' => 1, 'max' => 100, 'when' => function($m) { return $m->key == 'max_brightness'; }],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDetail()
    {
        return $this->hasOne(ExamSettingAvail::className(), ['key' => 'key']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBelongsTo()
    {
        return $this->hasOne(ExamSetting::className(), ['id' => 'belongs_to']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMembers()
    {
        return $this->hasMany(ExamSetting::className(), ['belongs_to' => 'id']);
    }

    /**
     * Load the default value from the database
     * @param skipIfSet Whether existing value should be preserved.
     * This will only set defaults for attributes that are null.
     */
    public function loadDefaultValue($skipIfSet = true)
    {
        if ( ($this->value === null || ($this->value !== null && !$skipIfSet)) && $this->detail !== null) {
            $this->value = $this->detail->default;
        }
    }

}
