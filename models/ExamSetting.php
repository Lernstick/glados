<?php

namespace app\models;

use Yii;
use yii\helpers\Markdown;
use yii\helpers\ArrayHelper;
use app\models\ExamSettingAvail;
use app\components\HistoryBehavior;

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
    public function behaviors()
    {
        $models = ExamSettingAvail::find()->all();
        $attr = array_combine(array_column($models, 'key'), array_column($models, 'type'));
        return [
            'HistoryBehavior' => [
                'class' => HistoryBehavior::className(),
                'track_deletion' => true,
                'track_insertion' => true,
                'relation' => 'exam',
                'columnAttribute' => 'key',
                'attributes' => array_merge([
                    'value' => 'text',
                ], $attr),
            ],
        ];
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
            ['value', 'filter', 'filter' => function ($v) { return $v/100;}, 'when' => function($m) { return $m->key == 'max_brightness'; }],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        $models = ExamSettingAvail::find()->all();
        return array_combine(array_column($models, 'key'), array_column($models, 'name'));
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        $models = ExamSettingAvail::find()->all();
        return array_combine(array_column($models, 'key'), array_column($models, 'description'));
    }

    /**
     * @inheritdoc 
     */
    public function jsonRules()
    {
        return [
            'libre_autosave' => 'boolval',
            'libre_autosave_interval' => 'intval',
            'libre_createbackup' => 'boolval',
            'screenshots' => 'boolval',
            'screenshots_interval' => 'intval',
            'max_brightness' => function($v){return intval($v*100);},
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
     * @return \yii\db\ActiveQuery
     */
    public function getExam()
    {
        return $this->hasOne(Exam::className(), ['id' => 'exam_id']);
    }

    /**
     * @return mixed
     */
    public function getFormattedValue()
    {
        $value = $this->value;
        if (array_key_exists($this->key, $this->jsonRules())) {
            $rule = $this->jsonRules()[$this->key];
            if (is_callable($rule)) {
                $value = $rule($this->value);
            }
        }
        return $value;
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
