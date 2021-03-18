<?php

use yii\db\Migration;

/**
 * Class m210318_195814_issues
 */
class m210318_195814_issues extends Migration
{

    public $issuesTable = 'issue';
    public $ticketTable = 'ticket';
    public $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        // create the issues table
        if ($this->db->schema->getTableSchema($this->issuesTable, true) === null) {
            $this->createTable($this->issuesTable, [
                'id' => $this->primaryKey(),
                'key' => $this->integer(11)->notNull(),
                'occuredAt' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
                'solvedAt' => $this->timestamp()->null(),
                'ticket_id' => $this->integer(11)->notNull(),
            ], $this->tableOptions);

            $this->createIndex('idx-ticket_id', $this->issuesTable, 'ticket_id');

            $this->addForeignKey(
                'fk-issue_ticket_id',
                $this->issuesTable,
                'ticket_id',
                $this->ticketTable,
                'id',
                'CASCADE',
                'CASCADE'
            );

        }

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        if ($this->db->schema->getTableSchema($this->issuesTable, true) !== null) {
            $this->dropForeignKey('fk-issue_ticket_id', $this->issuesTable);
            $this->dropTable($this->issuesTable);
        }
    }
}
