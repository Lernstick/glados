<?php

use app\components\BaseMigration;

/**
 * Class m210330_120233_v1_0_9
 * Mark version 1.0.8
 */
class m210330_120233_v1_0_9 extends BaseMigration
{

    public $examTable = 'exam';
    public $historyTable = 'history';
    public $issuesTable = 'issue';
    public $ticketTable = 'ticket';

    public $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';

    public $fields = [
        'password' => 'user',
    ];


    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // split allow_mount exam setting
        $this->addColumn($this->examTable, 'allow_mount_system', $this->boolean()->notNull()->defaultValue(0));
        $this->renameColumn($this->examTable, 'allow_mount', 'allow_mount_external');

        // set allow_mount_system to allow_mount_external
        Yii::$app->db->createCommand()->update($this->examTable,
            ['allow_mount_system' => new yii\db\Expression('`allow_mount_external`')])->execute();

        // change allow_mount to allow_mount_external in history table
        Yii::$app->db->createCommand()->update($this->historyTable, [
            'column' => 'allow_mount_external',
        ], [
            'table' => 'exam',
            'column' => 'allow_mount',
        ])->execute();

        // enforce strict mode
        foreach ($this->fields as $field => $table) {
            $this->alterColumn($table, $field, $this->string(60)->notNull()->defaultValue(''));
        }

        // create issues table
        if ($this->db->schema->getTableSchema($this->issuesTable, true) === null) {
            $this->createTable($this->issuesTable, [
                'id' => $this->primaryKey(),
                'key' => $this->integer(11)->notNull(),
                'occuredAt' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
                'solvedAt' => $this->timestamp()->null(),
                'ticket_id' => $this->integer(11)->notNull(),
            ], $this->tableOptions);

            $this->createIndex('idx-ticket_id', $this->issuesTable, 'ticket_id');

            $this->addForeignKey(
                'fk-issue_ticket_id',
                $this->issuesTable,
                'ticket_id',
                $this->ticketTable,
                'id',
                'CASCADE',
                'CASCADE'
            );

        }

        // updates the translation table with the newest translations
        $this->updateTranslationTable();

        // removes translation table entries that are not referenced
        $this->cleanTranslationTable();

        echo "db version 1.0.9 installed\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        /* revert issues table */
        if ($this->db->schema->getTableSchema($this->issuesTable, true) !== null) {
            $this->dropForeignKey('fk-issue_ticket_id', $this->issuesTable);
            $this->dropTable($this->issuesTable);
        }

        /* revert strict mode */
        foreach ($this->fields as $field => $table) {
            $this->alterColumn($table, $field, $this->string(60)->notNull());
        }

        /* fuse allow mount exam setting */
        $this->dropColumn($this->examTable, 'allow_mount_system');
        $this->renameColumn($this->examTable, 'allow_mount_external', 'allow_mount');

        // change allow_mount_external back to allow_mount in history table
        Yii::$app->db->createCommand()->update($this->historyTable, [
            'column' => 'allow_mount',
        ], [
            'table' => 'exam',
            'column' => 'allow_mount_external',
        ])->execute();
    }
}
