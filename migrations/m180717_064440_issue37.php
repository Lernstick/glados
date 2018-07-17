<?php

use yii\db\Migration;
use app\models\EventStream;
use yii\db\Expression;

class m180717_064440_issue37 extends Migration
{

    public $eventStreamTable = 'event_stream';

    // give the event_stream table a started_at column to later remove the row in the dbclean mechanism
    public function safeUp()
    {
        $this->addColumn($this->eventStreamTable, 'started_at', $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'));
        EventStream::updateAll(['started_at' => new Expression('NOW()')], []);
    }

    public function safeDown()
    {
        $this->dropColumn($this->eventStreamTable, 'started_at');
    }
}
