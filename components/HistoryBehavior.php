<?php

namespace app\components;

use yii\base\Behavior;
use yii\base\Event;
use app\models\History;

class HistoryBehavior extends Behavior
{
    /**
     * @var array list of attributes that are to be tracked in the history table.
     * The array keys are the ActiveRecord events upon which the attributes are to be tracked,
     * and the array values are the corresponding attribute(s) to be tracked. For example,
     *
     * ```php
     * [
     *     ActiveRecord::EVENT_BEFORE_INSERT => ['attribute1', 'attribute2'],
     *     ActiveRecord::EVENT_BEFORE_UPDATE => 'attribute2',
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
            array_keys($this->attributes),
            'historyEntry'
        );
    }

    /**
     * Creates a history table entry.
     * @param Event $event
     */
    public function historyEntry($event)
    {

        if (!empty($this->attributes[$event->name])) {

            $attributes = (array) $this->attributes[$event->name];
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
                // ignore attribute names which are not string (e.g. when set by TimestampBehavior::updatedAtAttribute)

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

}