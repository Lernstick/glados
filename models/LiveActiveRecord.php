<?php

namespace app\models;

use Yii;

/**
 * This is the model for live fields in ActiveRecord's
 */
class LiveActiveRecord extends TranslatedActiveRecord
{

    /**
     * @var array An array holding the values of the record before changing
     */
    private $presaveAttributes;

    /**
     * @inheritdoc
     */
    public function init()
    {

        $instance = $this;
        $this->on(self::EVENT_BEFORE_UPDATE, function($instance){
            $this->presaveAttributes = $this->getOldAttributes();
        });

        // For each live field, such an event needs to be fired
        foreach ($this->liveFields as $field => $config) {

            // if the field is given as string without a config, take the default config
            if (is_int($field)) {
                $field = $config;
                $config = [];
            }
            $this->on(self::EVENT_AFTER_UPDATE, [$this, 'updateEvent'], [$field, $config]);
        }

        parent::init();
    }

    /**
     * The default configuration for a live field if no other is given.
     * These fields are merged with the provided array in a way that the
     * values of the given array overwrite the ones below.
     * 
     * @return array The default configuration
     */
    private function defaultConfig() {
        return [
            'event' => function ($field, $model) {
                // default value is table/id
                return $model->tableName() . '/' . $model->id;
            },
            'priority' => 0,
            'data' => function ($field, $model) {
                // default value is [key => value]
                return in_array($field, $model->translatedFields)
                    ? [ $field => $this->{$field . '_db'} ]
                    : [ $field => $this->{$field} ];

            },
            'category' => function ($field, $model) {
                // check whether the field is a translated field
                return in_array($field, $model->translatedFields) ? $model->tableName() : null;
            },
            'translate_data' => function ($field, $model) {
                // check whether the field is a translated field
                $retval = null;
                if (in_array($field, $model->translatedFields)) {
                    $paramsField = $model->{$field . '_params'};
                    $retval = $paramsField == [] ? null : [ $field => $paramsField ];
                }
                return $retval;
            },
        ];
    }

    /**
     * A list of attributes whose modification triggers the event
     * 
     * @param string $field The live field
     * @param string $config its configuration
     * @return array List of properties
     */
    private function triggerAttributes($field, $config) {
        $list = [];
        /* if there are more additional fields, that trigger a change, add them to the list */
        if (array_key_exists('trigger_attributes', $config)) {
            foreach ($config['trigger_attributes'] as $key => $attribute) {

                if (in_array($attribute, $this->translatedFields)) {
                    $list[] = $attribute . '_id';
                    $list[] = $attribute . '_data';
                } else {
                    $list[] = $attribute;
                }
            }
        }
        if (in_array($field, $this->translatedFields)) {
            $list[] = $field . '_id';
            $list[] = $field . '_data';
        } else {
            $list[] = $field;
        }
        return $list;
    }

    /**
     * A list of database fields that are live. This should return something of the form:
     * 
     *  [
     *      'field1' => [
     *          # the event identifier, @see [[EventItem::event]]
     *          'event' => 'table/id',
     *          # @see [[EventItem::priority]]
     *          'priority' => integer,
     *          # if it is a pseudo field (calculated), these fields tell when a change is triggered
     *          'trigger_attributes' => ['fieldA', 'fieldB'],
     *          # @see [[EventItem::data]]
     *          'data' => [
     *              'field1' => 'value',
     *          ],
     *          ...
     *      ],
     *      # not all config fields must be set (the rest if filled by [[defaultConfig()]])
     *      'field2' => [
     *          # this example has a post-processed value for the field
     *          # @param $field the field name ("field2" in this example)
     *          # @param $model the current model
     *          'data' => [
     *              'field2' => function($field, $model){..},
     *          ],
     *          ...
     *      ],
     *      # can also be only a string, if no special config is needed (@see [[defaultConfig()]])
     *      'field3',
     *      ...
     *  ]
     * 
     * @return array
     */
    public function getLiveFields()
    {
        return [];
    }

    /**
     * Checks if attributes have changed
     * 
     * @param array $attributes - a list of attributes to check
     * @return bool
     */
    public function attributesChanged($attributes)
    {
        foreach($attributes as $attribute){
            if (array_key_exists($attribute, $this->presaveAttributes) && array_key_exists($attribute, $this->attributes)) {
                if ($this->presaveAttributes[$attribute] != $this->attributes[$attribute]){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * The event to generate the EventItem's based on the given config
     *
     * @param yii\base\Event $event the event
     * @return void
     */
    public function updateEvent($event)
    {
        if ($event->data !== null) {
            $field = $event->data[0];
            $config = $event->data[1];

            if ($this->attributesChanged($this->triggerAttributes($field, $config))) {

                // merge the default config with the provided one
                $realConfig = array_merge($this->defaultConfig(), $config);

                // evaluate the anonymous functions
                foreach ($realConfig as $key => $value) {
                    if (is_callable($value)) {
                        $realConfig[$key] = $value($field, $this);
                    }
                }

                //var_dump($realConfig);

                $eventItem = new EventItem($realConfig);
                $eventItem->generate();
            }
        }
    }
}
