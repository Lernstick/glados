<?php

use yii\db\Migration;

# dummy migration to mark version 1.0.5
class m190320_161727_v1_0_5 extends Migration
{
    public function safeUp()
    {
        echo "db version 1.0.5 installed\n";
    }

    public function safeDown()
    {
        return true;
    }
}
