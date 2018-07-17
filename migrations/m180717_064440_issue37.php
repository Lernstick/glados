<?php

use yii\db\Migration;
use app\models\EventStream;
use yii\db\Expression;

class m180717_064440_issue37 extends Migration
{

    public $eventStreamTable = 'event_stream';

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
