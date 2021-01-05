<?php

namespace app\components;

use yii\base\Behavior;
use yii\helpers\Inflector;

class ElasticsearchBehavior extends Behavior
{

    /**
     * @var array list of fields that should be replicated to elasticsearch.
     * The array keys are the corresponding attribute name(s) in elasticsearch and
     * the values defined the actual meaning (or the value). For example,
     *
     * ```php
     * [
     *     'attribute1' => ['value' => 'table.field'],
     *     'attribute2' => [],
     *     ...
     * ]
     * ```
     */
    public $fields = [];

    /**
     * @var array mappings
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping.html#field-datatypes
     */
    public $mappings = [];

    /**
     * @var array settings
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/index-modules.html#index-modules-settings
     */
    public $settings = [];

    /**
     * @var callable|array function or array to find all models
     * If it's a function, it must have the following signature:
     *   @param $class the class name
     *   @return $class[] an array of $class objects
     * By default the function returns (see [[init()]]):
     * ```$class::find()->all();```
     *
     * If it's an array it must have the following structure:
     * ```
     * [
     *      'foreach' => function($class) {} // function that returns a list of $models
     *      'models'  => function($model) {} // this function is called with each model in $models
     * ]
     * ```
     */
    public $allModels;

    /**
     * @var string the name of the index
     */
    public $index;

    /**
     * @var string|int the value that should be taken for the _id field
     */
    public $id;

    /**
     * @var array An array holding the attribute names as key and their values as value before changing
     */
    private $presaveAttributes = [];

    /**
     * @var array An array holding the attribute names as key and their values as value after changing
     */
    private $attributes = [];

    /**
     * @var array An array holding all attributes that cause a change
     */
    private $_trigger_attributes = [];

    /**
     * @inheritdoc 
     */
    public function init()
    {
        if ($this->allModels == null) {
            $this->allModels = function($class) { return $class::find()->all(); };
        }
    }

    /**
     * @return string the id of the document
     */
    public function getIdField()
    {
        return $this->id === null ? $this->owner->id : call_user_func($this->id, $this->owner);
    }

    /**
     * @inheritdoc 
     */
    public function events()
    {
        return [
            \yii\db\ActiveRecord::EVENT_BEFORE_UPDATE  => 'preUpdateDocument',
            \yii\db\ActiveRecord::EVENT_AFTER_UPDATE   => 'updateDocument',
            \yii\db\ActiveRecord::EVENT_AFTER_INSERT   => 'insertDocument',
            \yii\db\ActiveRecord::EVENT_AFTER_DELETE   => 'deleteDocument',
        ];
    }

    /**
     * Writes down the all attributes before saving
     * @param yii\base\ModelEvent $event
     * @return void
     */
    public function preUpdateDocument($event)
    {
        $this->presaveAttributes = $this->owner->getOldAttributes();
    }

    /**
     * Getter for the involved attributes
     * @return array attributes
     */
    public function getTrigger_attributes()
    {
        if (empty($this->_trigger_attributes)) {
            foreach ($this->fields as $key => $value) {
                if (is_int($key)) {
                    $this->_trigger_attributes[] = $value;
                } else if (is_array($value)) {
                    if (array_key_exists('trigger_attributes', $value)) {
                        foreach ($value['trigger_attributes'] as $attr) {
                            $this->_trigger_attributes[] = $attr;
                        }
                    } else {
                        $this->_trigger_attributes[] = $key;
                    }
                } elseif (!is_callable($value)) {
                    $this->_trigger_attributes[] = $key;
                }
            }
        }
        return $this->_trigger_attributes;
    }

    /**
     * Checks if attributes have changed
     * 
     * @return bool
     */
    public function attributesChanged()
    {
        foreach($this->trigger_attributes as $attribute){
            if (array_key_exists($attribute, $this->presaveAttributes) && array_key_exists($attribute, $this->attributes)) {
                if ($this->presaveAttributes[$attribute] != $this->attributes[$attribute]){
                    return true;
                }
            }
        }
        return empty($this->presaveAttributes);
    }

    /**
     * Updates the entry in elasticsearch.
     * @param yii\db\AfterSaveEvent $event
     * @return int|false the number of rows affected or false if the command threw an Exception.
     */
    public function updateDocument($event)
    {
        //$this->deleteIndex();
        //$this->createIndex();
        /**
         * @var array options to be appended to the query URL, such as "search_type" for search or
         * "timeout" for delete
         */
        $options = [
            'doc_as_upsert' => 'true', // If the document does not already exist, it will be inserted
        ]; 

        $this->attributes = $this->owner->getAttributes($this->trigger_attributes);

        $values = [];
        if ($this->attributesChanged()) {
            foreach ($this->fields as $key => $value) {
                if (is_int($key)) {
                    $values[$value] = $this->attributes[$value];
                } elseif (is_array($value)) {
                    if (array_key_exists('value_from', $value)) {
                        $values[$key] = $this->owner->{$value['value_from']};
                    } else {
                        $values[$key] = $this->owner->{$key};
                    }
                } elseif (is_callable($value)) {
                    $values[$key] = $value($this->owner);
                } else {
                    $values[$key] = $this->owner->{$value};
                }
            }
        }

        try {
            $db = \yii\elasticsearch\ActiveRecord::getDb();
            $response = $db->createCommand()->update(
                $this->index,
                \yii\elasticsearch\ActiveRecord::type(),
                $this->idField,
                $values,
                $options
            );
        } catch (\Exception $e) {
            \Yii::warning($e->getMessage(), __CLASS__);
            var_dump($e->getMessage());
            return false;
        }

        if ($response === false) {
            return 0;
        } else {
            return 1;
        }
    }

    /**
     * Inserts the entry in elasticsearch.
     * @param yii\db\AfterSaveEvent $event
     * @return bool whether the record is inserted successfully.
     */
    public function insertDocument($event)
    {
        if (method_exists($this->owner, 'refresh')) {
            $this->owner->refresh();
        }
        return $this->updateDocument($event);
    }

    /**
     * Deletes the entry in elasticsearch.
     * @param \yii\db\Event $event
     * @return int|flase the number of rows deleted or false if the command threw an Exception.
     */
    public function deleteDocument($event)
    {
        /**
         * @var array options to be appended to the query URL, such as "search_type" for search or
         * "timeout" for delete
         */
        $options = [];

        try {
            $response = \yii\elasticsearch\ActiveRecord::getDb()->createCommand()->delete(
                $this->index,
                \yii\elasticsearch\ActiveRecord::type(),
                $this->idField,
                $options
            );
        } catch (\Exception $e) {
            \Yii::warning($e->getMessage(), __CLASS__);
            return false;
        }

        if ($response === false) {
            return 0;
        } else {
            return 1;
        }
    }

    /**
     * Set (update) mappings for the model.
     * If the mapping is changed in a way that allows mapping update (e.g. created a new property)
     * then this emthod will update the mappings.
     */
    public function updateMapping()
    {
        \yii\elasticsearch\ActiveRecord::getDb()->createCommand()->setMapping(
            $this->index,
            \yii\elasticsearch\ActiveRecord::type(),
            [
                'properties' => $this->mappings['properties'],
            ]
        );
    }

    /**
     * Create the model's index.
     */
    public function createIndex()
    {
        $configuration = [
            'mappings' => $this->mappings,
        ];

        if (!empty($this->settings)) {
            $configuration['settings'] = $this->settings;
        }
        \yii\elasticsearch\ActiveRecord::getDb()->createCommand()->createIndex(
            $this->index,
            $configuration
        );
    }

    /**
     * Delete the model's index.
     */
    public function deleteIndex()
    {
        \yii\elasticsearch\ActiveRecord::getDb()->createCommand()->deleteIndex(
            $this->index,
            \yii\elasticsearch\ActiveRecord::type()
        );
    }

}