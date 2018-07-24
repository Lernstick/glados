<?php

use yii\db\Migration;

# dummy migration to mark version 1.0.4
class m180724_125845_v1_0_4 extends Migration
{
    public function safeUp()
    {
        echo "db version 1.0.4 installed\n";
    }

    public function safeDown()
    {
        return true;
    }
}
