<?php

use yii\db\Migration;
use app\models\User;

/**
 * Class m190829_075244_ldap
 */
class m190829_075244_ldap extends Migration
{
    public $userTable = 'user';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn($this->userTable, 'type', $this->string(255)->notNull()->defaultValue('0'));
        $this->addColumn($this->userTable, 'identifier', $this->string(255)->null());

        $auth = Yii::$app->authManager;

        $indexAuth = $auth->createPermission('auth/index');
        $indexAuth->description = 'Index all authentication methods';
        $auth->add($indexAuth);

        $createAuth = $auth->createPermission('auth/create');
        $createAuth->description = yiit('permission', 'Create a new authentication method');
        $auth->add($createAuth);

        $viewAuth = $auth->createPermission('auth/view');
        $viewAuth->description = yiit('permission', 'View all authentication methods');
        $auth->add($viewAuth);

        $updateAuth = $auth->createPermission('auth/update');
        $updateAuth->description = yiit('permission', 'Update all authentication methods');
        $auth->add($updateAuth);

        $deleteAuth = $auth->createPermission('auth/delete');
        $deleteAuth->description = yiit('permission', 'Delete all authentication methods');
        $auth->add($deleteAuth);

        $testAuth = $auth->createPermission('auth/test');
        $testAuth->description = yiit('permission', 'Test all authentication methods');
        $auth->add($testAuth);

        $migrateAuth = $auth->createPermission('auth/migrate');
        $migrateAuth->description = yiit('permission', 'Migrate existing users to users associated to an authentication method');
        $auth->add($migrateAuth);

        /* Assign permissions */
        $admin = $auth->getRole('admin');
        $auth->addChild($admin, $indexAuth);
        $auth->addChild($admin, $createAuth);
        $auth->addChild($admin, $viewAuth);
        $auth->addChild($admin, $updateAuth);
        $auth->addChild($admin, $deleteAuth);
        $auth->addChild($admin, $testAuth);

        $updateAllUsers = $auth->getPermission('user/update/all');
        $auth->addChild($updateAllUsers, $migrateAuth);

        /* drop unique index from user table */
        $this->dropIndex('username', $this->userTable);

        /* add unique index for username and type combined */
        $this->createIndex('uc_username_type', $this->userTable, ['username', 'type'], true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {

        // search for duplicated usernames
        $models = (new yii\db\Query())
            ->from($this->userTable)
            ->select(['id', 'username', 'COUNT(*) AS c'])
            ->groupBy(['username'])
            ->having('c > 1')
            ->all();

        // array with duplicated usernames
        $usernames = array_column($models, 'username');

        foreach ($usernames as $key => $username) {
            // get all ids with that duplicated username
            $columns = (new yii\db\Query())
                ->from($this->userTable)
                ->select(['id'])
                ->where(['username' => $username])
                ->andWhere(['not', ['type' => '0']])
                ->all();

            // array with ids of entries with duplicated usernames
            $ids = array_column($columns, 'id');

            // loop through all these ids and generate a random hashed username
            // such that they are unique again.
            foreach ($ids as $key => $id) {
                $this->update($this->userTable, ['username' => 'duplicate_' . generate_uuid()], ['id' => $id]);
            }
        }

        $this->dropColumn($this->userTable, 'type');
        $this->dropColumn($this->userTable, 'identifier');

        $auth = Yii::$app->authManager;

        $indexAuth = $auth->getPermission('auth/index');
        $createAuth = $auth->getPermission('auth/create');
        $viewAuth = $auth->getPermission('auth/view');
        $updateAuth = $auth->getPermission('auth/update');
        $deleteAuth = $auth->getPermission('auth/delete');
        $testAuth = $auth->getPermission('auth/test');
        $migrateAuth = $auth->getPermission('auth/migrate');

        $admin = $auth->getRole('admin');
        $updateAllUsers = $auth->getPermission('user/update/all');
        $auth->removeChild($admin, $indexAuth);
        $auth->removeChild($admin, $createAuth);
        $auth->removeChild($admin, $viewAuth);
        $auth->removeChild($admin, $updateAuth);
        $auth->removeChild($admin, $deleteAuth);
        $auth->removeChild($admin, $testAuth);
        $auth->removeChild($updateAllUsers, $migrateAuth);

        $auth->remove($indexAuth);
        $auth->remove($createAuth);
        $auth->remove($viewAuth);
        $auth->remove($updateAuth);
        $auth->remove($deleteAuth);
        $auth->remove($testAuth);
        $auth->remove($migrateAuth);

        /* drop the combined unique index from user table */
        $this->dropIndex('uc_username_type', $this->userTable);

        /* add unique index for username  */
        $this->createIndex('username', $this->userTable, 'username', true);
    }
}
