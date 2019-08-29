<?php

use yii\db\Migration;

/**
 * Class m190829_075244_ldap
 */
class m190829_075244_ldap extends Migration
{
    public $userTable = 'user';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn($this->userTable, 'type', $this->string(255)->notNull()->defaultValue('local'));
        $this->addColumn($this->userTable, 'identifier', $this->string(255)->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn($this->userTable, 'type');
        $this->dropColumn($this->userTable, 'identifier');
    }
}
