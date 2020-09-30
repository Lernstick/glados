<?php

namespace app\models\forms;

use Yii;
use app\models\EventItem;
use yii\helpers\Json;

/**
 * This is the form model class for event items.
 *
 * @inheritdoc
 *
 */
class EventItemSend extends EventItem
{

    public $nrOfTimes;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['nrOfTimes'], 'safe'],
            ['nrOfTimes', 'integer', 'min' => 1],
            [['event', 'priority', 'data'], 'required'],
            ['data', 'filter', 'filter' => function ($value) {
                $decoded = json_decode($value, true);
                return $decoded === null ? $value : $decoded;
            }],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'data' => 'Can contain <code>$i</code>, which is replaced by the counter. Examples: <ul><li><code>{"backup_state":"network error."}</code></li><li><code>this is event number $i</code></li><li><code>test</code></li></ul>',
        ];
    }

    /**
     * @return void
     */
    public function generateEvents()
    {
        for ($i = 0; $i < $this->nrOfTimes; $i++) { 
            $e = new EventItemSend();
            $e->attributes = $this->attributes;
            $e->data = str_replace("\$i", $i+1, $e->data);
            $e->generate();
        }
    }

}
