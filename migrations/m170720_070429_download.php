<?php

use yii\db\Migration;

class m170720_070429_download extends Migration
{
    public $ticketTable = 'ticket';

    public function safeUp()
    {
        $this->addColumn($this->ticketTable, 'download_request', $this->timestamp()->null()->defaultValue(null));
        $this->addColumn($this->ticketTable, 'download_finished', $this->timestamp()->null()->defaultValue(null));
        $this->addColumn($this->ticketTable, 'download_state', $this->string(255)->null()->defaultValue(null));
    }

    public function safeDown()
    {
        $this->dropColumn($this->ticketTable, 'download_request');
        $this->dropColumn($this->ticketTable, 'download_finished');
        $this->dropColumn($this->ticketTable, 'download_state');
    }
}
