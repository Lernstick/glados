<?php

namespace app\components;

use yii\base\Behavior;
use yii\base\Event;

class HistoryConnectionBehavior extends Behavior
{

    public $id;


    /**
     * @inheritdoc 
     */
    public function events()
    {
        return [
            // establishes a connection id
            \yii\db\Connection::EVENT_BEGIN_TRANSACTION => 'historyId',
        ];
    }

    /**
     * TODO
     * @param Event $event
     */
    public function historyId($event)
    {
        //$transaction = \Yii::$app->db->transaction;
        //save transaction id here
    }

}