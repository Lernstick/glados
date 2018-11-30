<?php

use yii\db\Migration;

/**
 * Class m181130_205238_activity_severity
 */
class m181130_205238_activity_severity extends Migration
{

    public $activityTable = 'activity';

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn($this->activityTable, 'severity', $this->integer(11)->defaultValue(null));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn($this->activityTable, 'severity');
    }

}
