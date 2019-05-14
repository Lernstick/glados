<?php

use yii\db\Migration;

/**
 * Class m190514_121720_i18n
 */
class m190514_121720_i18n extends Migration
{

    public $activitiesTable = 'activity';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn($this->activitiesTable, 'data', $this->string(1024)->defaultValue(null));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn($this->activitiesTable, 'data');
    }
}
