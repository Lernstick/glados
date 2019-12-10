<?php

use yii\db\Migration;
use app\models\Activity;
use app\models\Translation;

/**
 * Class m190531_162336_i18n_post
 *
 * This migration cleans up after the i18n migration
 */
class m190531_162336_i18n_post extends Migration
{

    public $activitiesTable = 'activity';
    public $ticketTable = 'ticket';

    public $translationTable = 'translation';
    public $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';

    private function fieldTypes() {
        return [
            'description' => $this->string(255)->notNull(),
            'client_state' => $this->string(255)->defaultValue('Client not seen yet'),
            'backup_state' => $this->text() . ' NULL DEFAULT NULL',
            'restore_state' => $this->text() . ' NULL DEFAULT NULL',
            'download_state' => $this->string(255) . ' NULL DEFAULT NULL',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // activity->description
        $this->tableFieldUp($this->activitiesTable, 'description');

        // ticket->client_state
        $this->tableFieldUp($this->ticketTable, 'client_state');

        // ticket->backup_state
        $this->tableFieldUp($this->ticketTable, 'backup_state');

        // ticket->restore_state
        $this->tableFieldUp($this->ticketTable, 'restore_state');

        // ticket->download_state
        $this->tableFieldUp($this->ticketTable, 'download_state');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // activity->description
        $this->tableFieldDown($this->activitiesTable, 'description');

        // ticket->client_state
        $this->tableFieldDown($this->ticketTable, 'client_state');

        // ticket->backup_state
        $this->tableFieldDown($this->ticketTable, 'backup_state');

        // ticket->restore_state
        $this->tableFieldDown($this->ticketTable, 'restore_state');

        // ticket->download_state
        $this->tableFieldDown($this->ticketTable, 'download_state');
    }

    private function tableFieldUp($table, $field)
    {
        $oldField = $field . "_old";

        // drop table->field_old
        if ($this->db->schema->getTableSchema($table, true)->getColumn($oldField) !== null) {
            $this->dropColumn($table, $oldField);
        }
    }

    private function tableFieldDown($table, $field)
    {
        $newField = $field . "_new";
        $idField = $field . "_id";
        $type = $this->fieldTypes()[$field];

        // create table->field_new
        if ($this->db->schema->getTableSchema($table, true)->getColumn($newField) === null) {
            $this->addColumn($table, $newField, $type);
        }

        // change table->field_id
        if ($this->db->schema->getTableSchema($table, true)->getColumn($idField) !== null) {
            $this->alterColumn($table, $idField, $this->string(64)->notNull());
        }
    }

}
