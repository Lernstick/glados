<?php

use yii\db\Migration;
use app\models\Activity;
use app\models\ActivityDescription;

/**
 * Class m190514_121720_i18n
 */
class m190514_121720_i18n extends Migration
{

    public $activitiesTable = 'activity';
    public $descriptionTable = 'tr_activity_description';
    public $descriptionColumn = 'description_id';
    public $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        //the description table
        $this->createTable($this->descriptionTable, [
            'id' => $this->primaryKey(),
            'en' => $this->string(255)->notNull(),
            'de' => $this->string(255),
        ], $this->tableOptions);

        $this->addColumn($this->activitiesTable, 'data', $this->string(1024)->defaultValue(null));
        $this->renameColumn($this->activitiesTable, 'description', 'description_old');
        $this->addColumn($this->activitiesTable, $this->descriptionColumn, $this->integer(11)->notNull());

        $models = Activity::find()->all();
        foreach ($models as $model) {

            // TODO: loop through all languages
            $t = new ActivityDescription([
                'en' => $model->description_old,
                'de' => $model->description_old,
            ]);
            $t->save();

            $model->{$this->descriptionColumn} = $t->id;
            $model->update(false); // skipping validation as no user input is involved
        }

        $this->addForeignKey(
            'fk-activity-desc_de',
            $this->activitiesTable,
            $this->descriptionColumn,
            $this->descriptionTable,
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

        if ($this->db->schema->getTableSchema($this->descriptionTable, true) !== null) {
            // remove the foreign key
            $this->dropForeignKey('fk-activity-desc_de', $this->activitiesTable);
            $this->alterColumn($this->activitiesTable, $this->descriptionColumn, $this->string(64)->notNull());

            // truncate table
            $this->truncateTable($this->descriptionTable);

            // drop the table
            $this->dropTable($this->descriptionTable);
        }

        $this->dropColumn($this->activitiesTable, 'data');
        $this->dropColumn($this->activitiesTable, $this->descriptionColumn);
        $this->renameColumn($this->activitiesTable, 'description_old', 'description');
    }
}
