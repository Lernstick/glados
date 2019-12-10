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
        return $model === null ? $null : $model->value;
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
     * Increments a value of a given key or creates the entry with the given value if it does not exists.
     * 
     * @param string key the key to increment
     * @param int increment the incrementation value (defaults to +1)
     * @return boolean Whether the saving succeeded (i.e. no validation errors occurred).
     */
    public function increment($key, $increment = 1)
    {
        $old_value = static::get($key, 0);
        return static::set($key, $old_value + $increment);
    }

    /**
     * Finds entry by key
     *
     * @param string key
     * @return static|null ActiveRecord instance matching the condition, or null if nothing matches.
     */
    public static function findByKey($key)
    {
        return static::findOne(['key' => $key]);
    }

}
