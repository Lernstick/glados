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
            \yii\db\ActiveRecord::EVENT_AFTER_UPDATE => 'changeEvent',
            \yii\db\ActiveRecord::EVENT_AFTER_INSERT => 'insertEvent',
            \yii\db\ActiveRecord::EVENT_AFTER_DELETE => 'deleteEvent',
        ];
    }

    /**
     * Updates the entry in elasticsearch.
     * @param Event $event
     */
    public function changeEvent($event)
    {
        $attributes = (array) array_keys($this->attributes);

        $class = "\\app\\models\\elasticsearch\\" . Inflector::id2camel($this->index);
        $obj = new $class();
        $obj->_attributes = $attributes;
        $obj->_id = $this->owner->id;
        #$obj->tableName = $this->owner->tableName();

        // Intersection of both arrays are attributes with history entry.
        // These are changed according to $event->changedAttributes
        $changedAttr = array_intersect($attributes, array_keys($this->owner->dirtyAttributes));

        foreach ($changedAttr as $key => $attribute) {
            $obj->{$attribute} = $this->owner->{$attribute};
        }
        //var_dump($obj::index());
        $obj->save();
        return;
    }

    /**
     * Inserts the entry in elasticsearch.
     * @param Event $event
     */
    public function insertEvent($event)
    {
        //$test = new \app\models\elasticsearch\Test();
        //$test->_id = 1; // setting primary keys is only allowed for new records
        //$test->attributes = ['test_taker' => 'Jane'];
        //var_dump($test->save());
        #$m = \app\models\elasticsearch\Test::findOne(1);
        //$m = \app\models\Ticket::findOne(2942);
        //$test = new \app\models\elasticsearch\Test();
        #$test->_id = $m->id;
        /*foreach ($test->attributes() as $key => $attr) {
            $test->{$attr} = $m->{$attr};
            //$test->save();
        }*/
        //\app\models\elasticsearch\Test::deleteIndex();
        //\app\models\elasticsearch\Test::createIndex();
        /*$query = new \yii\elasticsearch\Query();
        $query->from('test');
        $query->addOptions(['track_total_hits' => 'true']);
        var_dump($query->all());*/
        //$test = \app\models\elasticsearch\Test::find()->all();
        //var_dump($test);

        return;
    }

    /**
     * Deletes the entry in elasticsearch.
     * @param Event $event
     */
    public function deleteEvent($event)
    {
        return;
    }

}