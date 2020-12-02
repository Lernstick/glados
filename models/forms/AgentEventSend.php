<?php

namespace app\models\forms;

use Yii;
use app\models\AgentEvent;
use app\models\Ticket;
use yii\helpers\Json;

/**
 * This is the form model class for agent event items.
 *
 * @inheritdoc
 *
 */
class AgentEventSend extends AgentEvent
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
            'event' => 'Use <code>agent/&lt;token&gt;</code>',
            'data' => 'Can contain <code>$i</code>, which is replaced by the counter. Examples: <ul><li><code>{"backup_state":"network error."}</code></li><li><code>this is event number $i</code></li><li><code>test</code></li></ul>',
        ];
    }

    /**
     * @return bool whether agent events could have been sent of not
     */
    public function generateEvents()
    {
        $token = substr($this->event, strlen("agent/"));
        if ( ($model = Ticket::findOne(['token' => $token])) !== null ){
            for ($i = 0; $i < $this->nrOfTimes; $i++) { 
                $e = new AgentEventSend();
                $e->attributes = $this->attributes;
                $e->ticket = $model;
                $e->data = str_replace("\$i", $i+1, $e->data);
                $e->generate();
            }
            return true;
        } else {
            return false;
        }
    }

}
