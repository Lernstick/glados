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
    public $descriptionTable = 'translation';
    public $descriptionColumn = 'description_id';
    public $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        if ($this->db->schema->getTableSchema($this->descriptionTable, true) === null) {

            //the description table
            // TODO: loop through all languages
            $this->createTable($this->descriptionTable, [
                'id' => $this->primaryKey(),
                'en' => $this->string(255)->notNull(),
                'de' => $this->string(255),
                //'foreign_id' => $this->integer(11),
            ], $this->tableOptions);

        }

        if ($this->db->schema->getTableSchema($this->activitiesTable, true)->getColumn('data') === null) {
            $this->addColumn($this->activitiesTable, 'data', $this->string(1024)->defaultValue(null));
        }

        if ($this->db->schema->getTableSchema($this->activitiesTable, true)->getColumn('description_old') === null) {
            $this->renameColumn($this->activitiesTable, 'description', 'description_old');
        }

        if ($this->db->schema->getTableSchema($this->activitiesTable, true)->getColumn($this->descriptionColumn) === null) {
            $this->addColumn($this->activitiesTable, $this->descriptionColumn, $this->integer(11)->notNull());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        if ($this->db->schema->getTableSchema($this->descriptionTable, true) !== null) {

            // truncate table
            $this->truncateTable($this->descriptionTable);

            // drop the table
            $this->dropTable($this->descriptionTable);

            if ($this->db->schema->getTableSchema($this->activitiesTable, true)->getColumn('description_new') !== null) {
                $this->renameColumn($this->activitiesTable, 'description_new', 'description');
            }
        }

        if ($this->db->schema->getTableSchema($this->activitiesTable, true)->getColumn('data') !== null) {
            $this->dropColumn($this->activitiesTable, 'data');
        }

        if ($this->db->schema->getTableSchema($this->activitiesTable, true)->getColumn($this->descriptionColumn) !== null) {
            $this->dropColumn($this->activitiesTable, $this->descriptionColumn);
        }

        if ($this->db->schema->getTableSchema($this->activitiesTable, true)->getColumn('description_old') !== null) {
            $this->renameColumn($this->activitiesTable, 'description_old', 'description');
        }

    }
}
