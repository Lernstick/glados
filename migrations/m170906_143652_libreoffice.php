<?php

use yii\db\Migration;

class m170906_143652_libreoffice extends Migration
{
    public $examTable = 'exam';

    public function safeUp()
    {
        $this->addColumn($this->examTable, 'libre_autosave', $this->boolean()->notNull()->defaultValue(0));
        $this->addColumn($this->examTable, 'libre_createbackup', $this->boolean()->notNull()->defaultValue(0));
        $this->addColumn($this->examTable, 'libre_autosave_interval', $this->integer(11)->notNull()->defaultValue(10));
    }

    public function safeDown()
    {
        $this->dropColumn($this->examTable, 'libre_autosave');
        $this->dropColumn($this->examTable, 'libre_createbackup');
        $this->dropColumn($this->examTable, 'libre_autosave_interval');
    }
}

