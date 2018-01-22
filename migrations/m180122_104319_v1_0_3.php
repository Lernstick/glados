<?php

use yii\db\Migration;

# dummy migration to mark version 1.0.3
class m180122_104319_v1_0_3 extends Migration
{
    public function safeUp()
    {
        echo "db version 1.0.3 installed\n";
    }

    public function safeDown()
    {
        return true;
    }
}
