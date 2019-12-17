<?php

use yii\db\Migration;
use app\models\Setting;


/**
 * Class m191217_072209_settings
 */
class m191217_072209_settings extends Migration
{

    public $settingsTable = 'setting';
    public $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // create the settings table
        if ($this->db->schema->getTableSchema($this->settingsTable, true) === null) {
            $this->createTable($this->settingsTable, [
                'id' => $this->primaryKey(),
                'date' => $this->timestamp()->null()->defaultExpression('CURRENT_TIMESTAMP'),
                'key' => $this->text(),
                'value' => $this->text(),
                'type' => $this->text(),
                'default_value_data' => $this->string(1024)->defaultValue(null),
                'default_value_id' => $this->integer(11)->notNull(),
            ], $this->tableOptions);
        }

        $loginHint = new Setting([
            'key' => yiit('setting', 'Login hint'),
            'type' => 'markdown',
            'default_value' => yiit('setting', "You may login with **admin/admin** or **teacher/teacher**.<br>To modify the users, please login as **admin**."),
        ]);
        $loginHint->save(false);

        $auth = Yii::$app->authManager;

        $indexSettings = $auth->createPermission('setting/index');
        $indexSettings->description = yiit('permission', 'Index/view all settings');
        $auth->add($indexSettings);

        $updateSettings = $auth->createPermission('setting/update');
        $updateSettings->description = yiit('permission', 'Update all settings');
        $auth->add($updateSettings);

        /* Assign permissions */
        $admin = $auth->getRole('admin');
        $auth->addChild($admin, $indexSettings);
        $auth->addChild($admin, $updateSettings);

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        if ($this->db->schema->getTableSchema($this->settingsTable, true) !== null) {
            $this->dropTable($this->settingsTable);
        }

        $auth = Yii::$app->authManager;

        $indexSettings = $auth->getPermission('setting/index');
        $updateSettings = $auth->getPermission('setting/update');

        $admin = $auth->getRole('admin');
        $auth->removeChild($admin, $indexSettings);
        $auth->removeChild($admin, $updateSettings);

        $auth->remove($indexSettings);
        $auth->remove($updateSettings);
    }
}
