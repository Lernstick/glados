<?php

use yii\db\Migration;

/**
 * Class m191224_143955_screen_capture
 */
class m191224_143955_screen_capture extends Migration
{
    public $examTable = 'exam';
    public $scTable = 'screen_capture';
    public $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        // create the screen_capture table
        if ($this->db->schema->getTableSchema($this->scTable, true) === null) {
            $this->createTable($this->scTable, [
                'id' => $this->primaryKey(),
                'enabled' => $this->boolean()->notNull()->defaultValue(0),
                'quality' => $this->integer(11)->notNull()->defaultValue(100),
                'command' => $this->text(),
            ], $this->tableOptions);

            $this->addColumn($this->examTable, 'screen_capture_id', $this->integer(11));
            $this->createIndex('idx-screen_capture_id', $this->examTable, 'screen_capture_id');


        }

        $this->addForeignKey(
            'fk-exam-screen_capture_id',
            $this->examTable,
            'screen_capture_id',
            $this->scTable,
            'id',
            'CASCADE',
            'CASCADE'
        );

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {

        if ($this->db->schema->getTableSchema($this->scTable, true) !== null) {
            $this->dropForeignKey('fk-exam-screen_capture_id', $this->examTable);
            $this->dropColumn($this->examTable, 'screen_capture_id');
            $this->dropTable($this->scTable);
        }

    }
}
