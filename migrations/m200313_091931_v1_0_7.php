<?php

use yii\db\Migration;

# dummy migration to mark version 1.0.7
class m200313_091931_v1_0_7 extends Migration
{
    public function safeUp()
    {
        echo "db version 1.0.7 installed\n";
    }

    public function safeDown()
    {
        return true;
    }
}
