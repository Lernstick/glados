<?php

use yii\db\Migration;

/**
 * Class m181109_134153_create_dates
 */
class m181109_134153_create_dates extends Migration
{
    public $examTable = 'exam';
    public $ticketTable = 'ticket';

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn($this->examTable, 'createdAt', $this->timestamp()->null()->defaultExpression('CURRENT_TIMESTAMP'));
        $this->addColumn($this->ticketTable, 'createdAt', $this->timestamp()->null()->defaultExpression('CURRENT_TIMESTAMP'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn($this->examTable, 'createdAt');
        $this->dropColumn($this->ticketTable, 'createdAt');
    }
}
