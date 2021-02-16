<?php

use yii\db\Migration;

/**
 * Class m210216_150047_scrict_mode
 */
class m210216_150047_scrict_mode extends Migration
{
    public $fields = [
        'password' => 'user',
    ];

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        foreach ($this->fields as $field => $table) {
            $this->alterColumn($table, $field, $this->string(60)->notNull()->defaultValue(''));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        foreach ($this->fields as $field => $table) {
            $this->alterColumn($table, $field, $this->string(60)->notNull());
        }
    }
}
