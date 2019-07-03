<?php

namespace app\components;

use yii\base\Behavior;
use yii\base\Event;
use app\models\History;

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
     * @inheritdoc 
     */
    public function events()
    {
        return array_fill_keys(
            [\yii\db\ActiveRecord::EVENT_AFTER_UPDATE],
            'historyEntry'
        );
    }

    /**
     * Creates a history table entry.
     * @param Event $event
     */
    public function historyEntry($event)
    {

        if (!empty($this->attributes[$event->name]) || $event->name == \yii\db\ActiveRecord::EVENT_AFTER_UPDATE) {

            $attributes = (array) array_keys($this->attributes);
            $date = microtime(true);
            $table = $this->owner->tableName();
            $row = $this->owner->id;
            $identity = $this->identity();
            $hash = bin2hex(openssl_random_pseudo_bytes(8));

            // if it's a translated field remove the attribute, but add the 
            // two real attributes attribute_id and attribute_data
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

            // create history entries for all attributes that have been changed
            foreach ($attributes as $attribute) {

                //$old_value = $this->owner->oldAttributes[$attribute];
                $new_value = $this->owner->$attribute;
                $old_value = isset($event->changedAttributes[$attribute])
                    ? $event->changedAttributes[$attribute]
                    : $new_value;

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
     * Gets the user identity or 0 if a console application has called the event
     * or -1 if the identity is unknown
     * @return integer The user id of the user changing the entry
     */
    private function identity()
    {
        if (get_class(\Yii::$app) == "yii\console\Application") {
            return 0;
        } else if (get_class(\Yii::$app) == "yii\web\Application") {
            return \Yii::$app->user->id;
        } else {
            return -1;
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