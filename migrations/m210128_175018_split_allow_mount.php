<?php

use yii\db\Migration;

/**
 * Class m210128_175018_split_allow_mount
 */
class m210128_175018_split_allow_mount extends Migration
{

    public $examTable = 'exam';
    public $historyTable = 'history';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
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
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
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
