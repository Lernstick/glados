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

    public $translationTable = 'translation';
    public $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        if ($this->db->schema->getTableSchema($this->translationTable, true) === null) {

            //the description table
            $this->createTable($this->translationTable, [
                'id' => $this->primaryKey(),
                'en' => $this->string(255)->notNull(),
                'de' => $this->string(255),
                //'foreign_id' => $this->integer(11),
            ], $this->tableOptions);

        }

        /* activity->description */
        if ($this->db->schema->getTableSchema($this->activitiesTable, true)->getColumn('description_data') === null) {
            $this->addColumn($this->activitiesTable, 'description_data', $this->string(1024)->defaultValue(null));
        }

        if ($this->db->schema->getTableSchema($this->activitiesTable, true)->getColumn('description_old') === null) {
            $this->renameColumn($this->activitiesTable, 'description', 'description_old');
        }

        if ($this->db->schema->getTableSchema($this->activitiesTable, true)->getColumn('description_id') === null) {
            $this->addColumn($this->activitiesTable, 'description_id', $this->integer(11)->notNull());
        }

        /*
         * Without changing these datatypes it would trow the following error:
         * Exception: SQLSTATE[42000]: Syntax error or access violation: 1118 Row size too large.
         * The maximum row size for the used table type, not counting BLOBs, is 65535. This includes
         * storage overhead,check the manual. You have to change some columns to TEXT or BLOBs.
         */
        $this->alterColumn($this->ticketTable, 'backup_state', 'text');
        $this->alterColumn($this->ticketTable, 'restore_state', 'text');

        /* ticket->client_state */
        if ($this->db->schema->getTableSchema($this->ticketTable, true)->getColumn('client_state_data') === null) {
            $this->addColumn($this->ticketTable, 'client_state_data', $this->string(64)->defaultValue(null));
        }

        if ($this->db->schema->getTableSchema($this->ticketTable, true)->getColumn('client_state_old') === null) {
            $this->renameColumn($this->ticketTable, 'client_state', 'client_state_old');
        }

        if ($this->db->schema->getTableSchema($this->ticketTable, true)->getColumn('client_state_id') === null) {
            $this->addColumn($this->ticketTable, 'client_state_id', $this->integer(11)->notNull());
        }

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        if ($this->db->schema->getTableSchema($this->translationTable, true) !== null) {

            // truncate table
            $this->truncateTable($this->translationTable);

            // drop the table
            $this->dropTable($this->translationTable);

            if ($this->db->schema->getTableSchema($this->activitiesTable, true)->getColumn('description_new') !== null) {
                $this->renameColumn($this->activitiesTable, 'description_new', 'description');
            }

            if ($this->db->schema->getTableSchema($this->ticketTable, true)->getColumn('client_state_new') !== null) {
                $this->renameColumn($this->ticketTable, 'client_state_new', 'client_state');
            }
        }

        /* activity->description */
        if ($this->db->schema->getTableSchema($this->activitiesTable, true)->getColumn('description_data') !== null) {
            $this->dropColumn($this->activitiesTable, 'description_data');
        }

        if ($this->db->schema->getTableSchema($this->activitiesTable, true)->getColumn('description_id') !== null) {
            $this->dropColumn($this->activitiesTable, 'description_id');
        }

        if ($this->db->schema->getTableSchema($this->activitiesTable, true)->getColumn('description_old') !== null) {
            $this->renameColumn($this->activitiesTable, 'description_old', 'description');
        }

        /* ticket->client_state */
        if ($this->db->schema->getTableSchema($this->ticketTable, true)->getColumn('client_state_data') !== null) {
            $this->dropColumn($this->ticketTable, 'client_state_data');
        }

        if ($this->db->schema->getTableSchema($this->ticketTable, true)->getColumn('client_state_id') !== null) {
            $this->dropColumn($this->ticketTable, 'client_state_id');
        }

        if ($this->db->schema->getTableSchema($this->ticketTable, true)->getColumn('client_state_old') !== null) {
            $this->renameColumn($this->ticketTable, 'client_state_old', 'client_state');
        }

        $this->alterColumn($this->ticketTable, 'backup_state', 'string(10240)');
        $this->alterColumn($this->ticketTable, 'restore_state', 'string(10240)');

    }
}
