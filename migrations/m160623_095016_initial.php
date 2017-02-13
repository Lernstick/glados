<?php

use yii\base\InvalidConfigException;
use yii\rbac\DbManager;
use yii\db\Migration;

class m160623_095016_initial extends Migration
{

    public $userTable = 'user';
    public $examTable = 'exam';
    public $ticketTable = 'ticket';
    public $activityTable = 'activity';
    public $restoreTable = 'restore';
    public $daemonTable = 'daemon';
    public $eventStreamTable = 'event_stream';
    public $eventTable = 'event';
    public $eventRoleJunctionTable = 'rel_event_role';
    public $eventUserJunctionTable = 'rel_event_user';
    public $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';


    /**
     * @throws yii\base\InvalidConfigException
     * @return DbManager
     */
    protected function getAuthManager()
    {
        $authManager = Yii::$app->getAuthManager();
        if (!$authManager instanceof DbManager) {
            throw new InvalidConfigException('You should configure "authManager" component to use database before executing this migration.');
        }
        return $authManager;
    }

    /**
     * @inheritdoc
     */
    public function safeUp()
    {

        if ($this->db->schema->getTableSchema($this->userTable, true) === null) {

            //the user table
            $this->createTable($this->userTable, [
            'id' => $this->primaryKey(),
                'access_token' => $this->string(254)->defaultValue(null),
                'auth_key' => $this->string(254)->defaultValue(null),
                'username' => $this->string(40)->notNull()->unique(),
                'password' => $this->string(60)->notNull(),
                'last_visited' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP') . ' ON UPDATE CURRENT_TIMESTAMP',
                //'activities_last_visited' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP')
                'activities_last_visited' => $this->timestamp(),
                'change_password' => $this->boolean()->notNull()->defaultValue(0)
            ], $this->tableOptions);

            $this->alterColumn($this->getAuthManager()->assignmentTable, 'user_id', $this->integer(11)->notNull());

            $this->addForeignKey(
                'fk-authassignment-user_id',
                $this->getAuthManager()->assignmentTable,
                'user_id',
                $this->userTable,
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        if ($this->db->schema->getTableSchema($this->examTable, true) === null) {

            //the exam table
            $this->createTable($this->examTable, [
                'id' => $this->primaryKey(),
                'name' => $this->string(64)->notNull(),
                'subject' => $this->string(64)->notNull(),
                'file' => $this->string(1024)->defaultValue(null),
                'md5' => $this->string(32)->defaultValue(null),
                'user_id' => $this->integer(11)->defaultValue(null),
            ], $this->tableOptions);

            $this->createIndex('idx-user_id', $this->examTable, 'user_id');

            $this->addForeignKey(
                'fk-exam-user_id',
                $this->examTable,
                'user_id',
                $this->userTable,
                'id',
                'SET NULL'
            );
        }

        if ($this->db->schema->getTableSchema($this->ticketTable, true) === null) {

            //the ticket table
            $this->createTable($this->ticketTable, [
                'id' => $this->primaryKey(),
                'token' => $this->string(16)->notNull(),
                'exam_id' => $this->integer(11)->notNull(),
                'start' => $this->timestamp() . ' NULL DEFAULT NULL',
                'end' => $this->timestamp() . ' NULL DEFAULT NULL',
                'ip' => $this->string(255) . ' NULL',
                'test_taker' => $this->string(64) . ' NULL',
                'download_progress' => $this->decimal(3, 2)->defaultValue(0.00),
                'download_lock' => $this->boolean()->defaultValue(0),
                'client_state' => $this->string(255)->defaultValue('Client not seen yet'),
                'backup_last' => $this->timestamp() . ' NULL DEFAULT NULL',
                'backup_last_try' => $this->timestamp() . ' NULL DEFAULT NULL',
                'backup_state' => $this->string(10240) . ' NULL DEFAULT NULL',
                'backup_lock' => $this->boolean()->notNull()->defaultValue(0),
                'running_daemon_id' => $this->integer(11) . ' NULL',
                'restore_lock' => $this->boolean()->notNull()->defaultValue(0),
                'restore_state' => $this->string(10240) . ' NULL DEFAULT NULL',
                'bootup_lock' => $this->boolean()->notNull()->defaultValue(1),
            ], $this->tableOptions);

            $this->createIndex('idx-token', $this->ticketTable, 'token');
            $this->createIndex('idx-exam_id', $this->ticketTable, 'exam_id');

            $this->addForeignKey(
                'fk-ticket-exam_id',
                $this->ticketTable,
                'exam_id',
                $this->examTable,
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        if ($this->db->schema->getTableSchema($this->activityTable, true) === null) {

            //the activity table
            $this->createTable($this->activityTable, [
                'id' => $this->primaryKey(),
                'date' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
                'description' => $this->string(255)->notNull(),
                'ticket_id' => $this->integer(11)->notNull(),
            ], $this->tableOptions);

            $this->createIndex('idx-ticket_id', $this->activityTable, 'ticket_id');

            $this->addForeignKey(
                'fk-activity-ticket_id',
                $this->activityTable,
                'ticket_id',
                $this->ticketTable,
                'id',
                'CASCADE',
                'CASCADE'
            );

        }

        if ($this->db->schema->getTableSchema($this->restoreTable, true) === null) {

            //the restore table
            $this->createTable($this->restoreTable, [
                'id' => $this->primaryKey(),
                'startedAt' => $this->timestamp() . ' NULL DEFAULT NULL',
                'finishedAt' => $this->timestamp() . ' NULL DEFAULT NULL',
                'ticket_id' => $this->integer(11)->notNull(),
                'file' => $this->string(255)->notNull(),
                'restoreDate' => $this->string(64)->notNull(),
            ], $this->tableOptions);

            $this->createIndex('idx-ticket_id', $this->restoreTable, 'ticket_id');

            $this->addForeignKey(
                'fk-restore-ticket_id',
                $this->restoreTable,
                'ticket_id',
                $this->ticketTable,
                'id',
                'CASCADE',
                'CASCADE'
            );

        }

        if ($this->db->schema->getTableSchema($this->daemonTable, true) === null) {

            //the daemon table
            $this->createTable($this->daemonTable, [
                'id' => $this->primaryKey(),
                'pid' => $this->integer(11)->notNull(),
                'uuid' => $this->string(36)->notNull(),
                'state' => $this->string(255)->notNull(),
                'description' => $this->string(255)->notNull(),
                'started_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
                'alive' => $this->timestamp() . ' NULL DEFAULT NULL',
            ], $this->tableOptions);

        }

        if ($this->db->schema->getTableSchema($this->eventStreamTable, true) === null) {

            //the event_stream table
            $this->createTable($this->eventStreamTable, [
                'id' => $this->primaryKey(),
                'uuid' => $this->string(36)->notNull(),
                'lastEventId' => $this->integer(11) . ' NULL DEFAULT NULL',
                'stopped_at' => $this->double('14,4')->notNull(),
            ], $this->tableOptions);

            $this->createIndex('idx-uuid', $this->eventStreamTable, 'uuid');

        }

        if ($this->db->schema->getTableSchema($this->eventTable, true) === null) {

            //the event table
            $this->createTable($this->eventTable, [
                'id' => $this->primaryKey(),
                'event' => $this->string(32)->notNull(),
                'data' => $this->string(2048)->notNull(),
                'generated_at' => $this->double('14,4')->notNull(),
                'priority' => $this->integer(1)->notNull()->defaultValue(0),
                'broadcast' => $this->boolean()->notNull()->defaultValue(0),
            ], $this->tableOptions);

        }


        if ($this->db->schema->getTableSchema($this->eventRoleJunctionTable, true) === null) {

            //the rel_event_role junction table
            $this->createTable($this->eventRoleJunctionTable, [
                'event_id' => $this->integer(11)->notNull(),
                'role' => $this->string(64)->notNull(),
            ], $this->tableOptions);

            $this->createIndex('idx-event_id', $this->eventRoleJunctionTable, 'event_id');
            $this->createIndex('idx-role', $this->eventRoleJunctionTable, 'role');

            $this->addForeignKey(
                'fk-rel_event_role-event_id',
                $this->eventRoleJunctionTable,
                'event_id',
                $this->eventTable,
                'id',
                'CASCADE',
                'CASCADE'
            );

            $this->addForeignKey(
                'fk-rel_event_role-role',
                $this->eventRoleJunctionTable,
                'role',
                $this->getAuthManager()->itemTable,
                'name',
                'CASCADE',
                'CASCADE'
            );

        }


        if ($this->db->schema->getTableSchema($this->eventUserJunctionTable, true) === null) {

            //the rel_event_role junction table
            $this->createTable($this->eventUserJunctionTable, [
                'event_id' => $this->integer(11)->notNull(),
                'user_id' => $this->integer(11)->notNull(),
            ], $this->tableOptions);

            $this->createIndex('idx-event_id', $this->eventUserJunctionTable, 'event_id');
            $this->createIndex('idx-user_id', $this->eventUserJunctionTable, 'user_id');

            $this->addForeignKey(
                'fk-rel_event_user-event_id',
                $this->eventUserJunctionTable,
                'event_id',
                $this->eventTable,
                'id',
                'CASCADE',
                'CASCADE'
            );

            $this->addForeignKey(
                'fk-rel_event_role-user_id',
                $this->eventUserJunctionTable,
                'user_id',
                $this->userTable,
                'id',
                'CASCADE',
                'CASCADE'
            );

        }


        //create initial admin user
        $this->insert($this->userTable, [
            'username' => 'admin',
            'auth_key' => '7XMwXhEUquyIbh2wxBlLB_XeEt7YQntN',
            'password' => '$2y$13$vk4dpmhkPHZE80BXs1s9SO9VkaNTD7G/zwfjV7mrZna3kB.y1rasi', //admin
        ]);

        //create initial teacher user
        $this->insert($this->userTable, [
            'username' => 'teacher',
            'auth_key' => 'k5AX6seKzLWTWwDxWrAsGmYXdfRBQtnp',
            'password' => '$2y$13$SH8ybX5Oa3ki9AzsdBjMC.8qcpSuv7DMZfEuW3qfV6wqQbl4SUkva', //teacher
        ]);

        //the role assignment of the admin user
        $admin = $this->getAuthManager()->getRole('admin');
        $this->getAuthManager()->assign($admin, 1);

        //the role assignment of the teacher user
        $teacher = $this->getAuthManager()->getRole('teacher');
        $this->getAuthManager()->assign($teacher, 2);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {

        $this->delete($this->userTable, ['id' => 1]);
        $this->delete($this->userTable, ['id' => 2]);

        if ($this->db->schema->getTableSchema($this->eventUserJunctionTable, true) !== null) {
            $this->dropTable($this->eventUserJunctionTable);
        }

        if ($this->db->schema->getTableSchema($this->eventRoleJunctionTable, true) !== null) {
            $this->dropTable($this->eventRoleJunctionTable);
        }

        if ($this->db->schema->getTableSchema($this->eventTable, true) !== null) {
            $this->dropTable($this->eventTable);
        }

        if ($this->db->schema->getTableSchema($this->eventStreamTable, true) !== null) {
            $this->dropTable($this->eventStreamTable);
        }

        if ($this->db->schema->getTableSchema($this->daemonTable, true) !== null) {
            $this->dropTable($this->daemonTable);
        }

        if ($this->db->schema->getTableSchema($this->restoreTable, true) !== null) {
            $this->dropTable($this->restoreTable);
        }

        if ($this->db->schema->getTableSchema($this->activityTable, true) !== null) {
            $this->dropTable($this->activityTable);
        }

        if ($this->db->schema->getTableSchema($this->ticketTable, true) !== null) {
            $this->dropTable($this->ticketTable);
        }

        if ($this->db->schema->getTableSchema($this->examTable, true) !== null) {
    	    $this->dropTable($this->examTable);
        }

        if ($this->db->schema->getTableSchema($this->userTable, true) !== null) {
            $this->dropForeignKey('fk-authassignment-user_id', $this->getAuthManager()->assignmentTable);
            $this->alterColumn($this->getAuthManager()->assignmentTable, 'user_id', $this->string(64)->notNull());
	        $this->dropTable($this->userTable);
       }

    }

}
