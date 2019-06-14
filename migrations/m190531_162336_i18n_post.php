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
    public $descriptionTable = 'translation';
    public $descriptionColumn = 'description_id';
    public $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        /*if (!isset($this->db->schema->getTableSchema($this->activitiesTable, true)->foreignKeys['fk-activity-desc_de'])) {
            $this->addForeignKey(
                'fk-activity-desc_de',
                $this->activitiesTable,
                $this->descriptionColumn,
                $this->descriptionTable,
                'id',
                'CASCADE',
                'CASCADE'
            );
        }*/

        if ($this->db->schema->getTableSchema($this->activitiesTable, true)->getColumn('description_old') !== null) {
            $this->dropColumn($this->activitiesTable, 'description_old');
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

        /*if ($this->db->schema->getTableSchema($this->descriptionTable, true) !== null) {
            if (isset($this->db->schema->getTableSchema($this->activitiesTable, true)->foreignKeys['fk-activity-desc_de'])) {
                // remove the foreign key
                $this->dropForeignKey('fk-activity-desc_de', $this->activitiesTable);
            }
        }*/

        if ($this->db->schema->getTableSchema($this->activitiesTable, true)->getColumn($this->descriptionColumn) !== null) {
            $this->alterColumn($this->activitiesTable, $this->descriptionColumn, $this->string(64)->notNull());
        }
    }
}
