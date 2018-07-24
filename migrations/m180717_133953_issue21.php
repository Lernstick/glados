<?php

use yii\db\Migration;

class m180717_133953_issue21 extends Migration
{

    public $examTable = 'exam';

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn($this->examTable, 'max_brightness', $this->integer(11)->notNull()->defaultValue(100));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn($this->examTable, 'max_brightness');
    }
}
