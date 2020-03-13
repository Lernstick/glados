<?php

use yii\db\Migration;

/**
 * Class m200313_091210_mariadb
 *
 * Fixes missing default values for NOT NULL fields caused in the l18n migrations
 * See https://marius.bloggt-in-braunschweig.de/2019/02/21/mariadb-bugfix-sorgt-fuer-ein-bisschen-aerger/
 */
class m200313_091210_mariadb extends Migration
{

    public $fields = [
        'backup_state_id' => 'ticket',
        'restore_state_id' => 'ticket',
        'client_state_id' => 'ticket',
        'download_state_id' => 'ticket',
        'description_id' => 'activity',
    ];

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        foreach ($this->fields as $field => $table) {
            $this->alterColumn($table, $field, $this->integer(11)->notNull()->defaultValue(0));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        foreach ($this->fields as $field => $table) {
            $this->alterColumn($table, $field, $this->integer(11)->notNull());
        }
    }
}
