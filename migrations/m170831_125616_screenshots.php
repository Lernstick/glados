<?php

use yii\db\Migration;

class m170831_125616_screenshots extends Migration
{
    public $examTable = 'exam';

    public function safeUp()
    {
        $this->addColumn($this->examTable, 'screenshots_interval', $this->integer(11)->notNull()->defaultValue(5));
    }

    public function safeDown()
    {
        $this->dropColumn($this->examTable, 'screenshots_interval');
    }
}
