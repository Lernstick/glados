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

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema($this->activitiesTable, true)->getColumn('description_old') !== null) {
            $this->dropColumn($this->activitiesTable, 'description_old');
        }

        if ($this->db->schema->getTableSchema($this->ticketTable, true)->getColumn('client_state_old') !== null) {
            $this->dropColumn($this->ticketTable, 'client_state_old');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        if ($this->db->schema->getTableSchema($this->activitiesTable, true)->getColumn('description_new') === null) {
            $this->addColumn($this->activitiesTable, 'description_new', $this->string(1024)->defaultValue(null));
        }

        if ($this->db->schema->getTableSchema($this->activitiesTable, true)->getColumn('description_id') !== null) {
            $this->alterColumn($this->activitiesTable, 'description_id', $this->string(64)->notNull());
        }

        if ($this->db->schema->getTableSchema($this->ticketTable, true)->getColumn('client_state_new') === null) {
            $this->addColumn($this->ticketTable, 'client_state_new', $this->string(255)->defaultValue('Client not seen yet'));
        }

        if ($this->db->schema->getTableSchema($this->ticketTable, true)->getColumn('client_state_id') !== null) {
            $this->alterColumn($this->ticketTable, 'client_state_id', $this->string(64)->notNull());
        }

    }
}
