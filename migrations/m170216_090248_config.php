<?php

use yii\db\Migration;

class m170216_090248_config extends Migration
{

    public $examTable = 'exam';

    public function safeUp()
    {

        // new config items
        $this->addColumn($this->examTable, 'grp_netdev', $this->boolean()->notNull()->defaultValue(0));
        $this->addColumn($this->examTable, 'allow_sudo', $this->boolean()->notNull()->defaultValue(0));
        $this->addColumn($this->examTable, 'allow_mount', $this->boolean()->notNull()->defaultValue(0));
        $this->addColumn($this->examTable, 'firewall_off', $this->boolean()->notNull()->defaultValue(0));
    }

    public function safeDown()
    {
        $this->dropColumn($this->examTable, 'grp_netdev');
        $this->dropColumn($this->examTable, 'allow_sudo');
        $this->dropColumn($this->examTable, 'allow_mount');
        $this->dropColumn($this->examTable, 'firewall_off');
    }

}
