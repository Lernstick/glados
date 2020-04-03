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

    private $_attributeLabels;
    private $_attributeHints;

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
        return [
            'HistoryBehavior' => [
                'class' => HistoryBehavior::className(),
                'track_deletion' => true,
                'track_insertion' => true,
                'relation' => 'exam',
                'columnAttribute' => 'key',
                'attributes' => [
                    'value'                     => 'text',
                    "libre_autosave"            => "boolean",
                    "libre_autosave_interval"   => "integer",
                    "libre_autosave_path"       => "text",
                    "libre_createbackup"        => "boolean",
                    "libre_createbackup_path"   => "text",
                    "max_brightness"            => "percent",
                    "screenshots"               => "boolean",
                    "screenshots_interval"      => "integer",
                    "url_whitelist"             => "ntext",
                    "screen_capture"            => "boolean",
                    "screen_capture_command"    => "ntext",
                    "screen_capture_fps"        => "integer",
                    "screen_capture_quality"    => "percent",
                    "screen_capture_chunk"      => "integer",
                    "screen_capture_bitrate"    => "text",
                    "screen_capture_path"       => "text",
                ],
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
            ['value', 'required', 'when' => function($m) { return $m->key == 'url_whitelist'; }],
            ['value', 'filter', 'filter' => function ($v) { return $v/100;}, 'when' => function($m) { return $m->key == 'screen_capture_quality'; }],
        ];
    }

    /**
     * @TODO
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
            'url_whitelist' => function($v){return implode(PHP_EOL, preg_split("/\r\n|\n|\r/", $v, null, PREG_SPLIT_NO_EMPTY));},
            'screen_capture' => 'boolval',
            'screen_capture_command' => function($v){return implode(PHP_EOL, preg_split("/\r\n|\n|\r/", $v, null, PREG_SPLIT_NO_EMPTY));},
            'screen_capture_chunk' => 'intval',
            'screen_capture_fps' => 'intval',
            'screen_capture_quality' => function($v){return intval($v*100);},
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        if (empty($this->_attributeLabels)) {
            $models = ExamSettingAvail::find()->all();
            $this->_attributeLabels = array_combine(array_column($models, 'key'), array_column($models, 'name'));
        }
        return $this->_attributeLabels;
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        if (empty($this->_attributeHints)) {
            $models = ExamSettingAvail::find()->all();
            $this->_attributeHints = array_combine(array_column($models, 'key'), array_column($models, 'description'));
        }
        return $this->_attributeHints;
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
     * @return mixed Getter for the value in json
     */
    public function getJsonValue()
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
