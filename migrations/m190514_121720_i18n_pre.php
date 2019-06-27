<?php

use yii\db\Migration;
use app\models\Activity;
use app\models\Translation;

/**
 * Class m190514_121720_i18n_pre
 *
 * This migration sets up all neccessary tables for the i18n migration
 */
class m190514_121720_i18n_pre extends Migration
{

    public $activitiesTable = 'activity';
    public $ticketTable = 'ticket';
    public $eventTable = 'event';

    public $translationTable = 'translation';
    public $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        // create translation table
        if ($this->db->schema->getTableSchema($this->translationTable, true) === null) {
            $this->createTable($this->translationTable, [
                'id' => $this->primaryKey(),
                'en' => $this->string(255)->notNull(),
                'de' => $this->string(255),
                //'foreign_id' => $this->integer(11),
            ], $this->tableOptions);
        }

        /* activity->description */
        $this->tableFieldUp($this->activitiesTable, 'description');

        /*
         * Without changing these datatypes it would trow the following error:
         * Exception: SQLSTATE[42000]: Syntax error or access violation: 1118 Row size too large.
         * The maximum row size for the used table type, not counting BLOBs, is 65535. This includes
         * storage overhead,check the manual. You have to change some columns to TEXT or BLOBs.
         */
        $this->alterColumn($this->ticketTable, 'backup_state', 'text');
        $this->alterColumn($this->ticketTable, 'restore_state', 'text');

        /* ticket->client_state */
        $this->tableFieldUp($this->ticketTable, 'client_state');

        /* ticket->backup_state */
        $this->tableFieldUp($this->ticketTable, 'backup_state');

        /* ticket->restore_state */
        $this->tableFieldUp($this->ticketTable, 'restore_state');

        /* adjust event table */
        if ($this->db->schema->getTableSchema($this->eventTable, true)->getColumn('category') === null) {
            $this->addColumn($this->eventTable, 'category', $this->string(64)->defaultValue(null));
        }

        if ($this->db->schema->getTableSchema($this->eventTable, true)->getColumn('translate_data') === null) {
            $this->addColumn($this->eventTable, 'translate_data', $this->string(1024)->defaultValue(null));
        }

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // drop translation table
        if ($this->db->schema->getTableSchema($this->translationTable, true) !== null) {

            // truncate table
            $this->truncateTable($this->translationTable);

            // drop the table
            $this->dropTable($this->translationTable);
        }

        /* activity->description */
        $this->tableFieldDown($this->activitiesTable, 'description');

        /* ticket->client_state */
        $this->tableFieldDown($this->ticketTable, 'client_state');

        /* ticket->backup_state */
        $this->tableFieldDown($this->ticketTable, 'backup_state');

        /* ticket->restore_state */
        $this->tableFieldDown($this->ticketTable, 'restore_state');

        $this->alterColumn($this->ticketTable, 'backup_state', 'string(10240)');
        $this->alterColumn($this->ticketTable, 'restore_state', 'string(10240)');

        /* event table */
        if ($this->db->schema->getTableSchema($this->eventTable, true)->getColumn('category') !== null) {
            $this->dropColumn($this->eventTable, 'category');
        }

        if ($this->db->schema->getTableSchema($this->eventTable, true)->getColumn('translate_data') !== null) {
            $this->dropColumn($this->eventTable, 'translate_data');
        }
    }


    private function tableFieldUp($table, $field)
    {
        $dataField = $field . "_data";
        $idField = $field . "_id";
        $oldField = $field . "_old";

        // create table->field_data
        if ($this->db->schema->getTableSchema($table, true)->getColumn($dataField) === null) {
            $this->addColumn($table, $dataField, $this->string(1024)->defaultValue(null));
        }

        // create table->field_id
        if ($this->db->schema->getTableSchema($table, true)->getColumn($idField) === null) {
            $this->addColumn($table, $idField, $this->integer(11)->notNull());
        }

        // rename table->field to table->field_old
        if ($this->db->schema->getTableSchema($table, true)->getColumn($oldField) === null) {
            $this->renameColumn($table, $field, $oldField);
        }
    }

    private function tableFieldDown($table, $field)
    {
        $dataField = $field . "_data";
        $idField = $field . "_id";
        $oldField = $field . "_old";
        $newField = $field . "_new";

        // rename table->field_new to table->field
        if ($this->db->schema->getTableSchema($table, true)->getColumn($newField) !== null) {
            $this->renameColumn($table, $newField, $field);
        }

        // drop table->field_data
        if ($this->db->schema->getTableSchema($table, true)->getColumn($dataField) !== null) {
            $this->dropColumn($table, $dataField);
        }

        // drop table->field_id
        if ($this->db->schema->getTableSchema($table, true)->getColumn($idField) !== null) {
            $this->dropColumn($table, $idField);
        }

        // rename table->field_old to table->field
        if ($this->db->schema->getTableSchema($table, true)->getColumn($oldField) !== null) {
            $this->renameColumn($table, $oldField, $field);
        }
    }
}
