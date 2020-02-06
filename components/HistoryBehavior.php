<?php

namespace app\components;

use yii\base\Behavior;
use yii\base\Event;
use app\models\History;
use yii\db\Query;
use yii\helpers\Inflector;

class HistoryBehavior extends Behavior
{

    /**
     * @var array list of attributes that should be tracked in the history table.
     * The array keys are the corresponding attribute name(s) and the values are
     * the format of the attribute to be tracked. For example,
     *
     * ```php
     * [
     *     'attribute1' => 'text',
     *     'attribute2' => 'duration'],
     *     ...
     * ]
     * ```
     */
    public $attributes = [];

    /**
     * @var yii\db\ActiveQueryInterface property defining the relation of this entry
     * to the entry that should be taken as reference in the history table. For Example:
     *
     * 'exam' referring to a hasOne()/hasMany() relation
     *
     */
    public $relation;

    /**
     * @var string id of the current active transaction (if there is one)
     */
    public $commit_id;

    /**
     * @var string override the column attribute
     */
    public $columnAttribute;

    /**
     * @var bool create history entry for insertion of an item
     */
    public $track_insertion = false;

    /**
     * @var bool create history entry for deletion of an item
     */
    public $track_deletion = false;

    /**
     * @var bool create history entry for modification of an item
     */
    public $track_modification = true;

    /**
     * @inheritdoc 
     */
    public function events()
    {

        $ret = [];
        // adds new history records upon updating the main record
        if ($this->track_modification) {
            $ret[\yii\db\ActiveRecord::EVENT_AFTER_UPDATE] = 'changeEvent';
        }

        // adds new history records upon inserting the main record
        if ($this->track_insertion) {
            $ret[\yii\db\ActiveRecord::EVENT_AFTER_INSERT] = 'insertEvent';
        }

        // adds new history records upon deleting the main record
        if ($this->track_deletion) {
            $ret[\yii\db\ActiveRecord::EVENT_AFTER_DELETE] = 'deleteEvent';
        } else {
            $ret[\yii\db\ActiveRecord::EVENT_AFTER_DELETE] = 'recordDelete';
        }

        $ret[\yii\db\Connection::EVENT_BEGIN_TRANSACTION] = 'transactionBegin';
        return $ret;
    }

    /**
     * Find relation between a history item and the table/row it should be
     * related to. See [[relation]]
     */
    public function relation()
    {
        if ($this->relation !== null) {
            $table = $this->owner->{$this->relation}->tableName();
            $row = $this->owner->{$this->relation}->id;
        } else {
            $table = $this->owner->tableName();
            $row = $this->owner->id;
        }
        return [$table, $row];
    }

    /**
     * Creates a history entry for all changed entries that are in the attributes
     * array.
     * @param Event $event
     */
    public function changeEvent($event, $type = History::TYPE_UPDATE)
    {
        $hash = $this->determineHash();
        $date = microtime(true);
        list($table, $row) = $this->relation();
        $identity = $this->determineIdentity();
        $attributes = $this->determineAttributes();

        // Intersection of both arrays are attributes with history entry.
        // These are changed according to $event->changedAttributes
        $changedAttr = array_intersect($attributes, array_keys($event->changedAttributes));

        // create history entries for all attributes that have been changed
        foreach ($changedAttr as $attribute) {
            $new_value = $this->owner->$attribute;
            $old_value = $event->changedAttributes[$attribute];

            // only create a history entry if the old and new value differ
            if (is_string($attribute) && $old_value != $new_value) {

                $column = $this->determineColumn($attribute);

                $history = new History([
                    'table' => $table,
                    'column' => $column,
                    'row' => $row,
                    'changed_by' => $identity,
                    'changed_at' => $date,
                    'old_value' => $old_value,
                    'new_value' => $new_value,
                    'hash' => $hash,
                    'type' => $type,
                ]);
                $history->save();
            }
        }
    }

    /**
     * Creates a history entry for all inserted entries that are in the attributes
     * array.
     * @param Event $event
     */
    public function insertEvent($event)
    {
        $this->changeEvent($event, History::TYPE_INSERT);
    }

    /**
     * Creates a history entry for all deleted entries that are in the attributes
     * array.
     * @param Event $event
     */
    public function deleteEvent($event)
    {
        $hash = $this->determineHash();
        $date = microtime(true);
        list($table, $row) = $this->relation();
        $identity = $this->determineIdentity();
        $attributes = $this->determineAttributes();

        // Intersection of both arrays are attributes with history entry.
        // These are changed according to $event->changedAttributes
        $changedAttr = array_intersect($attributes, array_keys($this->owner->dirtyAttributes));

        foreach ($changedAttr as $attribute) {
            $column = $this->determineColumn($attribute);
            $sibling = History::find()->where([
                'table' => $table,
                'row' => $row,
                'column' => $column,
                'hash' => $hash,
                'changed_by' => $identity,
                'type' => History::TYPE_INSERT,
            ])->one();

            if ($sibling !== null) {
                $new_value = $sibling->new_value;
                $sibling->delete();
                $type = History::TYPE_UPDATE;
            } else {
                $new_value = null;
                $type = History::TYPE_DELETE;
            }
            $old_value = $this->owner->dirtyAttributes[$attribute];

            // only create a history entry if the old and new value differ
            if (is_string($attribute) && $old_value != $new_value) {

                $history = new History([
                    'table' => $table,
                    'column' => $column,
                    'row' => $row,
                    'changed_by' => $identity,
                    'changed_at' => $date,
                    'old_value' => $old_value,
                    'new_value' => $new_value,
                    'hash' => $hash,
                    'type' => $type,
                ]);
                $history->save();
            }
        }
    }

    /**
     * Determines the correct column name according to the config
     * @return string the attribute
     */
    public function determineColumn($attribute)
    {
        $column = $this->columnAttribute !== null
            ? $this->owner->{$this->columnAttribute}
            : $attribute;

        $column = $this->relation !== null
            ? $this->owner->tableName() . '.' . $column
            : $column;
        return $column;
    }

    /**
     * Returns all attributes that have to be tracked
     * @return array the attributes
     */
    public function determineAttributes()
    {
        $attributes = (array) array_keys($this->attributes);

        // if it's a translated field remove the attribute, but add the 
        // two real attributes "attribute_id" and "attribute_data"
        $inc = 0;
        if ($this->owner->hasMethod('getTranslatedFields')) {
            foreach ($attributes as $key => $attribute) {
                if (in_array($attribute, $this->owner->translatedFields)) {
                    array_splice($attributes, $key + $inc, 1, [
                        $attribute . '_id',
                        $attribute . '_data',
                    ]);
                    $inc++;
                }
            }
        }
        return $attributes;
    }

    /**
     * Returns the active commit_id if we are in an active transaction, else it
     * generates a new one.
     * @return string the hash
     */
    public function determineHash()
    {
        $transaction = \Yii::$app->db->transaction;
        // if we are in an active transaction, use the commit_id as hash
        if ($transaction !== null && $transaction->isActive) {
            $hash = \Yii::$app->db->getBehavior('history')->commit_id;
        } else {
            $hash = bin2hex(openssl_random_pseudo_bytes(8));
        }
        return $hash;
    }

    /**
     * Removes all history table entries corresponding to the item.
     * @param Event $event
     */
    public function recordDelete($event)
    {
        if ($event->name == \yii\db\ActiveRecord::EVENT_AFTER_DELETE) {
            list($table, $row) = $this->relation();
            History::deleteAll([
                'table' => $table,
                'row' => $row,
            ]);
        }
    }    

    /**
     * Gets the user id.
     *
     * The values that can be returned are as follows:
     *    * 0   if a console application has called the event
     *    * n>0 if a user id logged in and has caused the event
     *    * -1  the web application has caused the event, but noone was logged in => its the client
     *    * -2  unknown
     * @return integer The user id of the user changing the entry
     */
    private function determineIdentity()
    {
        if (get_class(\Yii::$app) == "yii\console\Application") {
            return 0;
        } else if (get_class(\Yii::$app) == "yii\web\Application") {
            if (\Yii::$app->user->id == null) {
                return -1;
            } else {
                return \Yii::$app->user->id;
            }
        } else {
            return -2;
        }
    }

    /**
     * Extract the format that is given in the [[attributes]] array.
     */
    public function formatOf($column, $default = 'text')
    {
        if (($pos = strrpos($column, '.')) !== false) {
            $model = substr($column, 0, $pos);
            $column = substr($column, $pos + 1);

            $model = $this->owner->getRelation($model);
            $class = $model->modelClass;
            $model = new $class();
            $behavior = $model->getBehavior('HistoryBehavior');
            $attributes = $behavior->attributes;
            unset($model, $behavior);
        } else {
            $attributes = $this->attributes;
        }

        return array_key_exists($column, $attributes)
            ? $attributes[$column]
            : $default;
    }

    /**
     * Determine the icon of the history item based on the value given in
     * the [[attributes]] array.
     */
    public function iconOf($model)
    {
        if ($model->new_value == '') {
            return '<i class="glyphicon glyphicon-log-out"></i>';
        } else if ($model->old_value == '') {
            return '<i class="glyphicon glyphicon-log-in"></i>';
        } else if ($this->formatOf($model->column) == 'boolean' && $model->new_value == 1) {
            return '<i class="glyphicon glyphicon-check"></i>';
        } else if ($this->formatOf($model->column) == 'boolean' && $model->new_value == 0) {
            return '<i class="glyphicon glyphicon-unchecked"></i>';
        } else {
            return '<i class="glyphicon glyphicon-edit"></i>';
        }
    }

    /**
     * Set an id for the whole transaction. This will then be used as hash for the 
     * history item to determine which items belong together.
     * @param Event $event
     */
    public function transactionBegin($event)
    {
        $this->commit_id = bin2hex(openssl_random_pseudo_bytes(8));
    }

}