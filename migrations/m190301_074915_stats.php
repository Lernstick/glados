<?php

use yii\db\Migration;
use app\models\Ticket;
use app\models\Exam;

/**
 * Class m190301_074915_stats
 */
class m190301_074915_stats extends Migration
{

    public $statsTable = 'stats';
    public $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        // get authManager instance and admin role
        $auth = Yii::$app->authManager;
        $admin = $auth->getRole('admin');

        // change config/system to system/config
        $systemConfig = $auth->createPermission('system/config');
        $systemConfig->description = 'View the system configuration';
        $auth->update('config/system', $systemConfig);
        
        // create new permission system status and assign it to the admin role
        $systemStats = $auth->createPermission('system/stats');
        $systemStats->description = 'View the system statistics';
        $auth->add($systemStats);
        $auth->addChild($admin, $systemStats);

        // create the stats table
        $this->createTable($this->statsTable, [
            'id' => $this->primaryKey(),
            'date' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'key' => $this->text(),
            'value' => $this->text(),
            'type' => $this->text(),
        ], $this->tableOptions);

        $this->insert($this->statsTable, [
            'key' => 'statsUpdatedAt',
            'value' => 0,
            'type' => 'boolean'
        ]);

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {

        // get authManager instance and admin role
        $auth = Yii::$app->authManager;
        $admin = $auth->getRole('admin');

        // change system/config back to config/system
        $systemConfig = $auth->createPermission('config/system');
        $systemConfig->description = 'View the system configuration';
        $auth->update('system/config', $systemConfig);
        
        // remove system/stats permission
        $systemStats = $auth->getPermission('system/stats');
        $auth->remove($systemStats);

        // remove the stats table
        $this->dropTable($this->statsTable);
    }

}
