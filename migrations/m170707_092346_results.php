<?php

use yii\db\Migration;

class m170707_092346_results extends Migration
{
    public $ticketTable = 'ticket';

    public function safeUp()
    {
        $this->addColumn($this->ticketTable, 'result', $this->string(255)->defaultValue(Null));
    }

    public function safeDown()
    {
        $this->dropColumn($this->ticketTable, 'result');
    }
}
