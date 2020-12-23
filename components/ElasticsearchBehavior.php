<?php

namespace app\components;

use yii\base\Behavior;
use yii\helpers\Inflector;

class ElasticsearchBehavior extends Behavior
{

    /**
     * @var array list of attributes that should be replicated to elasticsearcj.
     * The array keys are the corresponding attribute name(s) and the values are
     * the type of the attribute. For example,
     *
     * ```php
     * [
     *     'attribute1' => 'text',
     *     'attribute2' => 'date',
     *     ...
     * ]
     * ```
     */
    public $attributes = [];

    /**
     * @var string the index
     */
    public $index;

    /**
     * @inheritdoc 
     */
    public function events()
    {
        return [
            \yii\db\ActiveRecord::EVENT_AFTER_UPDATE => 'updateDocument',
            \yii\db\ActiveRecord::EVENT_AFTER_INSERT => 'insertDocument',
            \yii\db\ActiveRecord::EVENT_AFTER_DELETE => 'deleteDocument',
        ];
    }

    /**
     * Updates the entry in elasticsearch.
     * @param yii\db\AfterSaveEvent $event
     * @return int the number of rows affected.
     */
    public function updateDocument($event)
    {
        $options = [];
        $attributes = (array) array_keys($this->attributes);

        // Intersection of both arrays are attributes with that should be propagated to elasticsearch.
        // These are changed according to $event->changedAttributes
        $changedAttr = array_intersect($attributes, array_keys($event->changedAttributes));

        if (empty($changedAttr)) {
            return 0;
        }

        $values = [];
        foreach ($changedAttr as $attr) {
            $values[$attr] = $this->owner->{$attr};
        }

        $response = \yii\elasticsearch\ActiveRecord::getDb()->createCommand()->update(
            $this->index . "_inexistent",
            \yii\elasticsearch\ActiveRecord::type(),
            $this->owner->id,
            $values,
            $options
        );

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
        $options = [
            'op_type' => 'create',
        ];

        $attributes = (array) array_keys($this->attributes);

        $values = [];
        foreach ($attributes as $attr) {
            $values[$attr] = $this->owner->{$attr};
        }

        $response = \yii\elasticsearch\ActiveRecord::getDb()->createCommand()->insert(
            $this->index,
            \yii\elasticsearch\ActiveRecord::type(),
            $values,
            $this->owner->id,
            $options
        );

        return $response;
    }

    /**
     * Deletes the entry in elasticsearch.
     * @param \yii\db\Event $event
     * @return int the number of rows deleted.
     */
    public function deleteDocument($event)
    {
        $options = [];

        $response = \yii\elasticsearch\ActiveRecord::getDb()->createCommand()->delete(
            $this->index,
            \yii\elasticsearch\ActiveRecord::type(),
            $this->owner->id,
            $options
        );

        if ($response === false) {
            return 0;
        } else {
            return 1;
        }
    }

}