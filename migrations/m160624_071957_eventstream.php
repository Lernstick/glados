<?php

use yii\db\Migration;

class m160624_071957_eventstream extends Migration
{

    public $eventStreamTable = 'event_stream';

    public function safeUp()
    {

        //the event_stream table
        $this->addColumn($this->eventStreamTable, 'listenEvents', $this->string(2048)->notNull());
        $this->alterColumn($this->eventStreamTable, 'stopped_at', $this->double('14,4') . ' NULL DEFAULT NULL');

    }

    public function safeDown()
    {
    	$this->dropColumn($this->eventStreamTable, 'listenEvents');
    	$this->alterColumn($this->eventStreamTable, 'stopped_at', $this->double('14,4')->notNull());
    }
}
