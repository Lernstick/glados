<?php

use app\components\BaseMigration;

/**
 * Class m210705_145230_roles
 */
class m210705_145230_roles extends BaseMigration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;

        $indexRole = $auth->createPermission('role/index');
        $indexRole->description = yiit('permission', 'Index all roles');
        $auth->add($indexRole);

        $createRole = $auth->createPermission('role/create');
        $createRole->description = yiit('permission', 'Create a new role');
        $auth->add($createRole);

        $viewRole = $auth->createPermission('role/view');
        $viewRole->description = yiit('permission', 'View all roles');
        $auth->add($viewRole);

        $updateRole = $auth->createPermission('role/update');
        $updateRole->description = yiit('permission', 'Update all roles');
        $auth->add($updateRole);

        $deleteRole = $auth->createPermission('role/delete');
        $deleteRole->description = yiit('permission', 'Delete all roles');
        $auth->add($deleteRole);

        /* Assign permissions */
        $admin = $auth->getRole('admin');
        $auth->addChild($admin, $indexRole);
        $auth->addChild($admin, $createRole);
        $auth->addChild($admin, $viewRole);
        $auth->addChild($admin, $updateRole);
        $auth->addChild($admin, $deleteRole);

        $admin->description = yiit('permission', "The immutable 'admin' role");
        $auth->update('admin', $admin);

        $teacher = $auth->getRole('teacher');
        $teacher->description = yiit('permission', "The 'teacher' role");
        $auth->update('teacher', $teacher);

        $auth->invalidateCache(); // flush the RBAC cache, else permissions might not be up-to-date
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;

        $indexRole = $auth->getPermission('role/index');
        $createRole = $auth->getPermission('role/create');
        $viewRole = $auth->getPermission('role/view');
        $updateRole = $auth->getPermission('role/update');
        $deleteRole = $auth->getPermission('role/delete');

        $admin = $auth->getRole('admin');
        $auth->removeChild($admin, $indexRole);
        $auth->removeChild($admin, $createRole);
        $auth->removeChild($admin, $viewRole);
        $auth->removeChild($admin, $updateRole);
        $auth->removeChild($admin, $deleteRole);

        $auth->remove($indexRole);
        $auth->remove($createRole);
        $auth->remove($viewRole);
        $auth->remove($updateRole);
        $auth->remove($deleteRole);

        $auth->invalidateCache(); // flush the RBAC cache, else permissions might not be up-to-date
    }
}
