<?php

use yii\db\Migration;

/**
 * Class m190702_181050_history
 */
class m190702_181050_history extends Migration
{
    public $historyTable = 'history';
    public $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // create the history table
        if ($this->db->schema->getTableSchema($this->historyTable, true) === null) {
            $this->createTable($this->historyTable, [
                'id' => $this->primaryKey(),
                'table' => $this->string(64),
                'column' => $this->string(64),
                'row' => $this->integer(11),
                'changed_at' => $this->double('14,4')->notNull(),
                //$this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
                'changed_by' => $this->integer(11),
                'old_value' => $this->text(),
                'new_value' => $this->text(),
                'hash' => $this->string(16)->notNull(),
            ], $this->tableOptions);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        if ($this->db->schema->getTableSchema($this->historyTable, true) !== null) {
            $this->dropTable($this->historyTable);
        }
    }
}
