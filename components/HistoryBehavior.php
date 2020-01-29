<?php

namespace app\components;

use yii\base\Behavior;
use yii\base\Event;
use app\models\History;
use yii\db\Query;

class HistoryBehavior extends Behavior
{

    /**
     * @var array list of attributes that are to be tracked in the history table.
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
     * @var array array defining the relation of this entry to the entry that should
     * be taken as reference in the history table. For Example:
     *
     * ```php
     * ['exam', 'screen_capture_id']
     * ```
     *
     */
    public $relation;


    /**
     * @inheritdoc 
     */
    public function events()
    {
        return [
            // adds new history records upon updating the main record
            \yii\db\ActiveRecord::EVENT_AFTER_UPDATE => 'historyAdd',
            // removes records after deleting the main record
            \yii\db\ActiveRecord::EVENT_AFTER_DELETE => 'historyDelete'
        ];
    }

    /**
     * @inheritdoc 
     */
    public function relation()
    {
        if ($this->relation !== null) {
            list($foreign_table, $foreign_row) = $this->relation;
            $query = new Query;
            $query->select('id')
                ->from($foreign_table)
                ->where([$foreign_row => $this->owner->id])
                ->limit(1);
            $row = $query->one();
            if ($row !== false) {
                return [$foreign_table, $row['id']];
            }
        }
        return [$this->owner->tableName(), $this->owner->id];
    }

    /**
     * Creates a history entry for all changed entries that are in the attributes
     * array.
     * @param Event $event
     */
    public function historyAdd($event)
    {

        if ($event->name == \yii\db\ActiveRecord::EVENT_AFTER_UPDATE) {
            $attributes = (array) array_keys($this->attributes);
            $date = microtime(true);
            list($table, $row) = $this->relation();
            $identity = $this->identity();
            $hash = bin2hex(openssl_random_pseudo_bytes(8));

            // if it's a translated field remove the attribute, but add the 
            // two real attributes "attribute_id" and "attribute_data"
            $inc = 0;
            foreach ($attributes as $key => $attribute) {
                if ($this->owner->hasMethod('getTranslatedFields')
                    && in_array($attribute, $this->owner->translatedFields)
                ) {
                    array_splice($attributes, $key + $inc, 1, [
                        $attribute . '_id',
                        $attribute . '_data',
                    ]);
                    $inc++;
                }
            }

            // intersection of both arrays are attributes with history entry.
            // These are changed according to $event->changedAttributes
            $changedAttr = array_intersect($attributes, array_keys($event->changedAttributes));

            // create history entries for all attributes that have been changed
            foreach ($changedAttr as $attribute) {
                $new_value = $this->owner->$attribute;
                $old_value = $event->changedAttributes[$attribute];

                // only create a history entry if the old and new value differ
                if (is_string($attribute) && $old_value != $new_value) {
                    $history = new History([
                        'table' => $table,
                        'column' => $attribute,
                        'row' => $row,
                        'changed_by' => $identity,
                        'changed_at' => $date,
                        'old_value' => $old_value,
                        'new_value' => $new_value,
                        'hash' => $hash,
                    ]);
                    $history->save();
                }
            }
        }
    }

    /**
     * Removes all history table entries corresponding to the item.
     * @param Event $event
     */
    public function historyDelete($event)
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
    private function identity()
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
     * @TODO
     */
    public function formatOf($column, $default = 'text')
    {
        return array_key_exists($column, $this->attributes)
            ? $this->attributes[$column]
            : $default;
    }

    /**
     * @TODO
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

}