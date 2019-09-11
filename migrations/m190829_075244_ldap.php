<?php

use yii\db\Migration;

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
        $this->addColumn($this->userTable, 'type', $this->string(255)->notNull()->defaultValue('local'));
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

        /* Assign permissions */
        $admin = $auth->getRole('admin');
        $auth->addChild($admin, $indexAuth);
        $auth->addChild($admin, $createAuth);
        $auth->addChild($admin, $viewAuth);
        $auth->addChild($admin, $updateAuth);
        $auth->addChild($admin, $deleteAuth);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn($this->userTable, 'type');
        $this->dropColumn($this->userTable, 'identifier');

        $auth = Yii::$app->authManager;

        $indexAuth = $auth->getPermission('auth/index');
        $indexAuth = $auth->getPermission('auth/create');
        $indexAuth = $auth->getPermission('auth/view');
        $indexAuth = $auth->getPermission('auth/update');
        $indexAuth = $auth->getPermission('auth/delete');

        $admin = $auth->getRole('admin');
        $auth->removeChild($admin, $indexAuth);
        $auth->removeChild($admin, $createAuth);
        $auth->removeChild($admin, $viewAuth);
        $auth->removeChild($admin, $updateAuth);
        $auth->removeChild($admin, $deleteAuth);

        $auth->remove($indexAuth);
        $auth->remove($createAuth);
        $auth->remove($viewAuth);
        $auth->remove($updateAuth);
        $auth->remove($deleteAuth);
    }
}
