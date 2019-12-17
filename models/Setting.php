<?php

namespace app\models;

use Yii;
use yii\helpers\Markdown;
use app\models\LiveActiveRecord;

/**
 * This is the model class for table "setting".
 *
 * @property integer $id
 * @property string $key
 * @property string $value
 * @property string $default_value
 */
class Setting extends TranslatedActiveRecord
{

    /* db translated fields */
    public $default_value_db;
    public $default_value_orig;
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
            'default_value',
        ];
    }

    /**
     * @inheritdoc 
     */
    public function rules()
    {
        return [
            [['value', 'null'], 'safe'],
            ['null', 'filter', 'filter' => 'boolval', 'skipOnEmpty' => true],
            ['value', 'filter', 'filter' => [$this, 'processValue']],

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
            'value' => \Yii::t('setting', 'Value'),
            'null' => \Yii::t('setting', 'Use default value'),
            'type' => \Yii::t('setting', 'Type'),
            'default_value' => \Yii::t('setting', 'Default value'),
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
     * @param string key the key to search for
     * @param mixed null the value that should be returned if the entry was not found in the database
     * @return mixed|null the value corresponding to the key or null if no entry was found
     */
    public function get($key, $null = null)
    {
        $model = static::findByKey($key);
        if ( ($model = static::findByKey($key)) !== null ) {
            if ($model->value !== null) {
                return static::renderSetting($model->value, $model->type);
            } else {
                return static::renderSetting($model->default_value, $model->type);
            }
        } else {
            return $null;
        }
    }

    /**
     * Sets the value of a key or creates the entry if it does not exists.
     * 
     * @param string key the key to set
     * @param mixed value the value to set
     * @param string type the desired type in case when the entry is created.
     *        Can be one of the PHP data types
     *         * "boolean"
     *         * "integer"
     *         * "double" (aus historischen Gründen wird "double" im Fall eines float zurückgegeben, und
     *           nicht einfach float.),
     *         * "string"
     *         * "array"
     *         * "object"
     *         * "resource"
     *         * "resource (closed)" (von PHP 7.2.0 an)
     *         * "NULL"
     *         * "unknown type"
     * @return boolean Whether the saving succeeded (i.e. no validation errors occurred).
     * @see https://www.php.net/manual/de/function.gettype.php
     */
    public function set($key, $value, $type = 'string')
    {
        $model = static::findByKey($key);
        if ($model === null) {
            $model = new Stats();
            $model->key = $key;
            $model->value = $value;
            $model->type = $type;
        } else {
            $model->value = $value;
        }
        return $model->save();
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
