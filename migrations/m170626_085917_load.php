<?php

use yii\db\Migration;

class m170626_085917_load extends Migration
{

    public $daemonTable = 'daemon';

    public function safeUp()
    {
        $this->addColumn($this->daemonTable, 'load', $this->decimal(3, 2)->notNull()->defaultValue(0.00));
    }

    public function safeDown()
    {
        $this->dropColumn($this->daemonTable, 'load');
    }

}
