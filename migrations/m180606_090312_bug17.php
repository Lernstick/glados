<?php

use yii\db\Migration;
use app\models\Ticket;

/**
 * Class m180606_090312_bug17
 */
class m180606_090312_bug17 extends Migration
{

    public $ticketTable = 'ticket';

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn($this->ticketTable, 'last_backup', $this->boolean()->notNull()->defaultValue(0));
        Ticket::updateAll(['last_backup' => 1], [
            'and',
            ['not', ['start' => null]],
            ['not', ['end' => null]]
        ]);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn($this->ticketTable, 'last_backup');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180606_090312_bug17 cannot be reverted.\n";

        return false;
    }
    */
}
