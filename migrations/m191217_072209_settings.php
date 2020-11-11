<?php

use yii\db\Migration;
use yii\db\Query;


/**
 * Class m191217_072209_settings
 */
class m191217_072209_settings extends Migration
{

    public $settingsTable = 'setting';
    public $translationTable = 'translation';
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

        $en = yiit('setting', "You may login with **admin/admin** or **teacher/teacher**.<br>To modify the users, please login as **admin**.");
        $de = \Yii::t('setting', "You may login with **admin/admin** or **teacher/teacher**.<br>To modify the users, please login as **admin**.");

        // create initial translation manually
        $this->insert($this->translationTable, ['en' => $en, 'de' => $de]);

        // get its id
        $query = new Query;
        $id = $query->select('id')
            ->where(['en' => $en])
            ->from($this->translationTable)
            ->one()['id'];

        // conduct the settings entry manually
        $this->insert($this->settingsTable, [
            'key' => yiit('setting', 'Login hint'),
            'type' => 'markdown',
            'default_value_data' => null,
            'default_value_id' => $id,
        ]);

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

        // remove the translation
        $en = yiit('setting', "You may login with **admin/admin** or **teacher/teacher**.<br>To modify the users, please login as **admin**.");
        $this->delete($this->translationTable, ['en' => $en]);

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
