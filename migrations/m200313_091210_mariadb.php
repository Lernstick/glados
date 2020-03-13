<?php

use yii\db\Migration;

/**
 * Class m200313_091210_mariadb
 */
class m200313_091210_mariadb extends Migration
{

    public $fields = [
        'backup_state_id' => 'ticket',
    ];

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        foreach ($this->fields as $field => $table) {
            # code...
            $this->alterColumn($table, $field, $this->integer(11)->notNull()->defaultValue(0));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        foreach ($this->fields as $field => $table) {
            # code...
            $this->alterColumn($table, $field, $this->integer(11)->notNull());
        }
    }
}
