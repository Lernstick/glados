<?php

namespace app\models;

use Yii;
use yii\helpers\Markdown;
use yii\helpers\ArrayHelper;
use app\models\LiveActiveRecord;

/**
 * This is the model class for table "setting".
 *
 * @property integer $id
 * @property string $key
 * @property string $value
 * @property string $default_value
 * @property string $description
 */
class Setting extends TranslatedActiveRecord
{

    /* db translated fields */
    public $name_db;
    public $name_orig;
    public $default_value_db;
    public $default_value_orig;
    public $description_db;
    public $description_orig;

    public $null;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'setting';
    }

    /**
     * @inheritdoc
     */
    public function getTranslatedFields()
    {
        return [
            'name',
            'default_value',
            'description',
        ];
    }

    /**
     * @inheritdoc 
     */
    public function rules()
    {
        $rules = [
            [['value', 'null'], 'safe'],
            ['null', 'filter', 'filter' => 'boolval', 'skipOnEmpty' => true],
        ];

        // create row-wise rules according to rulesByKey()
        foreach ($this->rulesByKey() as $i => $rule) {
            $when = function($model) use ($rule) {
                return $model->key == $rule[0];
            };
            $whenClient = "function (attribute, value) {
                return $('#setting-key').val() == '" . $rule[0] . "';
            }";
            $append = [
                'value',
                $rule[1],
                'when' => $when,
                'whenClient' => $whenClient,
            ];
            foreach ($rule as $key => $value) {
                if (!is_int($key)) {
                    $append[$key] = $value;
                }
            }
            $rules[] = $append;
        }

        // this must be after all previous rules
        $rules[] = ['value', 'filter', 'filter' => [$this, 'processValue']];

        return $rules;
    }


    /**
     * A set of rules by key
     * @return array rules
     */
    public function rulesByKey()
    {
        return [
            ['tokenLength', 'integer', 'min' => 4, 'max' => 16],
            ['tokenLength', 'required'],
            ['loginHint', 'string', 'min' => 0, 'max' => 1024],
            ['upperBound', 'required'],
            ['upperBound', 'integer', 'min' => 30, 'max' => 100],
            ['lowerBound', 'required'],
            ['lowerBound', 'integer', 'min' => 10, 'max' => 100],
            ['minDaemons', 'required'],
            ['minDaemons', 'integer', 'min' => 1, 'max' => 100],
            ['maxDaemons', 'required'],
            ['maxDaemons', 'integer', 'min' => 1, 'max' => 100],
            ['abandonTicket', 'required'],
            ['abandonTicket', 'integer', 'min' => 1800, 'max' => 36000],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => \Yii::t('setting', 'ID'),
            'key' => \Yii::t('setting', 'Key'),
            'name' => \Yii::t('setting', 'Name'),
            'value' => \Yii::t('setting', 'Value'),
            'null' => \Yii::t('setting', 'Use default value'),
            'type' => \Yii::t('setting', 'Type'),
            'default_value' => \Yii::t('setting', 'Default value'),
            'description' => \Yii::t('setting', 'Description'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'id' => \Yii::t('setting', 'ID'),
            'key' => \Yii::t('setting', 'Key'),
            'value' => \Yii::t('setting', 'Value'),
            'null' => \Yii::t('setting', 'If this is set the default value is used instead. No value is saved then.'),
            'type' => \Yii::t('setting', 'Type'),
            'default_value' => \Yii::t('setting', 'Default value'),
        ];
    }

    /**
     * Returns the method of [[ActiveField]] to show in the edit form.
     */
    public function typeMapping()
    {
        return [
            'markdown' => ['textArea', ['maxlength' => true]],
            'integer' => ['textInput', ['type' => 'number']],
        ];
    }

    /**
     * Evaluate $value
     * @param string $value the value from the POST request
     * @return string the new value
     */
    public function processValue ($value) {
        return $this->null ? null : $value;
    }

    /**
     * Returns the value of a key
     *
     * @param string key the key
     * @param mixed null the value that should be returned if the entry was not found
     * @return mixed|null the value corresponding to the key or null if no entry was found
     */
    public function get($key, $null = null)
    {
        if (array_key_exists($key, \Yii::$app->params)) {
            return \Yii::$app->params[$key];
        } else {
            return $null;
        }
    }

    /**
     * Sets the value of a key (not persistent).
     * 
     * @param string key the key to set
     * @param mixed value the value to set
     * @param string type the desired type
     * @return void
     */
    public function set($key, $value, $type = 'string')
    {
        \Yii::$app->params[$key] = Setting::renderSetting($value, $type);
    }

    /**
     * Retrieves all settings from the database
     * 
     * @return void
     */
    public function repopulateSettings()
    {
        $old = \Yii::$app->params;
        $settings = Setting::find()->all();
        $fromDb = ArrayHelper::map($settings, 'key', function($model) {
            return Setting::renderSetting($model->value != null ? $model->value : $model->default_value, $model->type);
        });
        \Yii::$app->params = ArrayHelper::merge($old, $fromDb);
    }

    /**
     * Renders the value of a key
     *
     * @param mixed $value the value to render
     * @param string $type the type
     * @return string the rendered value
     */
    public function renderSetting($value, $type)
    {
        if ($type == "markdown") {
            return Markdown::process($value, 'gfm');
        } else {
            return $value;
        }
    }



    /**
     * Finds entry by key
     *
     * @param string key
     * @return static|null ActiveRecord instance matching the condition, or null if nothing matches.
     */
    public static function findByKey($key)
    {
        return static::find()->where(['key' => $key])->one();
    }

    /**
     * @inheritdoc
     * @return \yii\db\ActiveQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new TranslatedActivityQuery(get_called_class());
    }

}
