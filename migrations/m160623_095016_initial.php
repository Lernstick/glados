<?php

use yii\db\Migration;

class m160623_095016_initial extends Migration
{

    public $userTable = 'user2';
    public $examTable = 'exam2';

    public function safeUp()
    {

        $this->createTable($this->userTable, [
	    'id' => $this->primaryKey(),
            'access_token' => $this->string(254)->defaultValue(null),
	    'auth_key' => $this->string(254)->defaultValue(null),
            'username' => $this->string(40)->notNull()->unique(),
            'password' => $this->string(60)->notNull(),
            'last_visited' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP') . ' ON UPDATE CURRENT_TIMESTAMP',
            'activities_last_visited' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP')
        ]);

        //create initial admin user
        $this->insert($this->userTable, [
            'username' => 'admin',
//            'auth_key' => '7XMwXhEUquyIbh2wxBlLB_XeEt7YQntN',
            'password' => '$2y$13$vk4dpmhkPHZE80BXs1s9SO9VkaNTD7G/zwfjV7mrZna3kB.y1rasi', //admin
        ]);

        $this->createTable($this->examTable, [
            'id' => $this->primaryKey(),
            'name' => $this->string(64)->notNull(),
            'subject' => $this->string(64)->notNull(),
            'file' => $this->string(1024)->defaultValue(null),
            'user_id' => $this->integer()->defaultValue(null),
        ]);

        $this->createIndex(
            'idx-user_id',
            $this->examTable,
            'user_id'
        );

        $this->addForeignKey(
            'fk-exam-user_id',
            $this->examTable,
            'user_id',
            $this->userTable,
            'id',
            'CASCADE'
        );

	//create example exam
        $this->insert($this->examTable, [
            'name' => 'Example Exam',
            'subject' => 'Example Subject',
            'file' => '',
            'user_id' => 1,
        ]);


    }

    public function safeDown()
    {
	$this->dropTable($this->examTable);
	$this->dropTable($this->userTable);
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
