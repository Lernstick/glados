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
                    "screen_capture_chunk"      => "integer",
                    "screen_capture_bitrate"    => "text",
                    "screen_capture_path"       => "text",
                    "keylogger"                 => "boolean",
                    "keylogger_keymap"          => "text",
                    "keylogger_path"            => "text",
                ],
            ],
        ];
    }

    /**
     * @inheritdoc 
     */
    public function rules()
    {
        $rules = [
            [['value', 'key', 'belongs_to'], 'safe'],
            [['key'], 'required'],
        ];
        // contruct the rules according to [[settingRules()]]
        foreach ($this->settingRules() as $key => $rule) {
            $field = $rule[0];
            $validator = $rule[1];
            unset($rule[0]);
            unset($rule[1]);
            $parameters = $rule;
            $entry = [
                'value',
                $validator,
                'when' => function($m) use ($field) { return $m->key == $field; }
            ];
            foreach ($parameters as $key => $value) {
                $entry[$key] = $value;
            }
            $rules[] = $entry;
        }
        return $rules;
    }

    /**
     * Rules for the settings.
     * @return array rules as if the settings where active fields
     */
    public function settingRules()
    {
        return [
            ['libre_autosave', 'boolean'],
            ['libre_autosave_path', 'required'],
            ['libre_autosave_interval', 'required'],
            ['libre_autosave_interval', 'integer', 'min' => 1],
            ['libre_createbackup', 'boolean'],
            ['libre_createbackup_path', 'required'],
            ['screenshots', 'boolean'],
            ['screenshots_interval', 'required'],
            ['screenshots_interval', 'integer', 'min' => 1, 'max' => 100],
            ['max_brightness', 'required'],
            ['max_brightness', 'integer', 'min' => 1, 'max' => 100],
            ['max_brightness', 'filter', 'filter' => function ($v) { return $v/100;}],
            ['url_whitelist', 'required'],
            ['keylogger', 'boolean'],
            ['keylogger_keymap', 'required'],
            ['keylogger_keymap', 'string', 'length' => [1, 65535]],
            ['keylogger_path', 'required'],
            ['keylogger_path', 'string', 'length' => [1, 65535]],
            ['screen_capture', 'boolean'],
            ['screen_capture_fps', 'required'],
            ['screen_capture_fps', 'double', 'min' => 0.1, 'max' => 50],
            ['screen_capture_chunk', 'required'],
            ['screen_capture_chunk', 'double', 'min' => 1, 'max' => 300],
            ['screen_capture_bitrate', 'required'],
            ['screen_capture_bitrate', 'match', 'pattern' => '/^[0-9]{1,4}[\k|\K|\m|\M]$/'],
            ['screen_capture_overflow_threshold', 'required'],
            ['screen_capture_overflow_threshold', 'match', 'pattern' => '/(^[0-9]{1,4}\m$)|(^[0-9]{1,2}\%$)/'],
            ['screen_capture_command', 'required'],
            ['screen_capture_command', 'string', 'length' => [1, 65535]],
            ['screen_capture_path', 'required'],
            ['screen_capture_path', 'string', 'length' => [1, 65535]],
        ];
    }

    /**
     * The rules how to format the settings in the json configuration object
     * @return array rules as if the settings
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
            'screen_capture_chunk' => 'floatval',
            'screen_capture_fps' => 'floatval',
            'keylogger' => 'boolval',
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

        // set the current label
        if ($this->key !== null && array_key_exists($this->key, $this->_attributeLabels)) {
            $this->_attributeLabels['value'] = $this->_attributeLabels[$this->key];
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
