<?php

use yii\db\Migration;

# dummy migration to mark version 1.0.6
class m191217_072428_v1_0_6 extends Migration
{
    public function safeUp()
    {
        echo "db version 1.0.6 installed\n";
    }

    public function safeDown()
    {
        return true;
    }
}
