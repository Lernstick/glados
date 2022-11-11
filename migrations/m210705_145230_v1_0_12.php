<?php

use app\components\BaseMigration;

/**
 * Class m210705_145230_v1_0_12
 * Mark version 1.0.12
 */
class m210705_145230_v1_0_12 extends BaseMigration
{

    public $eventStreamTable = 'event_stream';
    public $daemonTable = 'daemon';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $auth = Yii::$app->authManager;
        $admin = $auth->getRole('admin');
        $teacher = $auth->getRole('teacher');

        $indexExam = $auth->getPermission('exam/index');
        $indexAllExam = $auth->getPermission('exam/index/all');
        $viewExam = $auth->getPermission('exam/view');
        $viewAllExam = $auth->getPermission('exam/view/all');
        $systemConfig = $auth->getPermission('config/system');
        $viewScreenshot = $auth->getPermission('screenshot/view');
        $snapScreenshot = $auth->getPermission('screenshot/snap');
        $createTicket = $auth->getPermission('ticket/create');
        $createAllTicket = $auth->getPermission('ticket/create/all');
        $indexTicket = $auth->getPermission('ticket/index');
        $indexAllTicket = $auth->getPermission('ticket/index/all');

        $indexRole = $auth->createPermission('role/index');
        $createRole = $auth->createPermission('role/create');
        $viewRole = $auth->createPermission('role/view');
        $updateRole = $auth->createPermission('role/update');
        $deleteRole = $auth->createPermission('role/delete');
        $serverStatus = $auth->createPermission('server/status');
        $monitorExam = $auth->createPermission('exam/monitor');
        $monitorAllExam = $auth->createPermission('exam/monitor/all');
        $pingTicket = $auth->createPermission('ticket/ping');
        $pingAllTicket = $auth->createPermission('ticket/ping/all');
        $serverLogs = $auth->createPermission('server/logs');

        $indexRole->description = yiit('permission', 'Index all roles');
        $createRole->description = yiit('permission', 'Create a new role');
        $viewRole->description = yiit('permission', 'View all roles');
        $updateRole->description = yiit('permission', 'Update all roles');
        $deleteRole->description = yiit('permission', 'Delete all roles');
        $serverStatus->description = yiit('permission', 'View the server status information');
        $monitorExam->description = yiit('permission', 'Monitor own exams');
        $monitorAllExam->description = yiit('permission', 'Monitor all exams');
        $viewExam->description = yiit('permission', 'View own exams');
        $viewAllExam->description = yiit('permission', 'View all exams');
        $pingTicket->description = yiit('permission', 'Ping clients of own tickets');
        $pingAllTicket->description = yiit('permission', 'Ping clients of all tickets');
        $serverLogs->description = yiit('permission', 'View/List the sever log files');
        $admin->description = yiit('permission', "The immutable 'admin' role");
        $teacher->description = yiit('permission', "The immutable 'teacher' role");

        yiit('permission', 'Submit results of all exams');
        yiit('permission', 'Submit results of own exams');
        yiit('permission', 'Generate result zip files of own exams');
        yiit('permission', 'Generate result zip files of all exams');

        $systemConfig->name = 'server/config';

        $auth->add($indexRole);
        $auth->add($createRole);
        $auth->add($viewRole);
        $auth->add($updateRole);
        $auth->add($deleteRole);
        $auth->add($serverStatus);
        $auth->add($monitorExam);
        $auth->add($monitorAllExam);
        $auth->add($pingTicket);
        $auth->add($pingAllTicket);
        $auth->add($serverLogs);

        $auth->addChild($monitorExam, $indexExam);
        $auth->addChild($monitorExam, $indexTicket);
        $auth->addChild($monitorAllExam, $monitorExam);
        $auth->addChild($monitorAllExam, $indexAllExam);
        $auth->addChild($monitorAllExam, $indexAllTicket);
        $auth->addChild($pingAllTicket, $pingTicket);

        $auth->update('exam/view', $viewExam); // rename "exam/view" description
        $auth->update('exam/view/all', $viewAllExam); // "exam/view/all" description
        $auth->update('config/system', $systemConfig); // rename "config/system" -> "server/config"
        $auth->update('admin', $admin); // set "admin" description
        $auth->update('teacher', $teacher); // set "teacher" description

        /* Assign new permissions */
        $auth->addChild($admin, $indexRole);
        $auth->addChild($admin, $createRole);
        $auth->addChild($admin, $viewRole);
        $auth->addChild($admin, $updateRole);
        $auth->addChild($admin, $deleteRole);
        $auth->addChild($admin, $serverStatus);
        $auth->addChild($admin, $monitorAllExam);
        $auth->addChild($admin, $pingAllTicket);
        $auth->addChild($admin, $serverLogs);
        $auth->addChild($teacher, $monitorExam);
        $auth->addChild($teacher, $pingTicket);

        // remove the "screenshot/view" and "screenshot/view" permissions, they are replaced
        // by the "ticket/view" permission.
        $auth->remove($viewScreenshot);
        $auth->remove($snapScreenshot);

        // Bug: "ticket/create" as child of "ticket/create/all"
        $auth->addChild($createAllTicket, $createTicket);

        $this->addColumn($this->eventStreamTable, 'watches', $this->integer()->notNull()->defaultValue(0));
        $this->addColumn($this->daemonTable, 'memory', $this->integer()->Null());

        // flush the RBAC cache, else permissions might not be up-to-date
        $auth->invalidateCache();
        Yii::$app->cache->flush();

        // updates the translation table with the newest translations
        $this->updateTranslationTable();

        // removes translation table entries that are not referenced
        $this->cleanTranslationTable();

        echo "db version 1.0.12 installed\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $auth = Yii::$app->authManager;
        $admin = $auth->getRole('admin');
        $teacher = $auth->getRole('teacher');

        $indexRole = $auth->getPermission('role/index');
        $createRole = $auth->getPermission('role/create');
        $viewRole = $auth->getPermission('role/view');
        $updateRole = $auth->getPermission('role/update');
        $deleteRole = $auth->getPermission('role/delete');
        $serverStatus = $auth->getPermission('server/status');
        $monitorExam = $auth->getPermission('exam/monitor');
        $monitorAllExam = $auth->getPermission('exam/monitor/all');
        $indexExam = $auth->getPermission('exam/index');
        $indexAllExam = $auth->getPermission('exam/index/all');
        $viewExam = $auth->getPermission('exam/view');
        $viewAllExam = $auth->getPermission('exam/view/all');
        $configServer = $auth->getPermission('server/config');
        $createAllTicket = $auth->getPermission('ticket/create/all');
        $createTicket = $auth->getPermission('ticket/create');
        $indexTicket = $auth->getPermission('ticket/index');
        $indexAllTicket = $auth->getPermission('ticket/index/all');
        $pingTicket = $auth->getPermission('ticket/ping');
        $pingAllTicket = $auth->getPermission('ticket/ping/all');
        $serverLogs = $auth->getPermission('server/logs');

        $viewScreenshots = $auth->createPermission('screenshot/view');
        $snapScreenshots = $auth->createPermission('screenshot/snap');

        $auth->removeChild($monitorExam, $indexExam);
        $auth->removeChild($monitorExam, $indexTicket);
        $auth->removeChild($monitorAllExam, $monitorExam);
        $auth->removeChild($monitorAllExam, $indexAllExam);
        $auth->removeChild($monitorAllExam, $indexAllTicket);
        $auth->removeChild($pingAllTicket, $pingTicket);
        $auth->removeChild($admin, $indexRole);
        $auth->removeChild($admin, $createRole);
        $auth->removeChild($admin, $viewRole);
        $auth->removeChild($admin, $updateRole);
        $auth->removeChild($admin, $deleteRole);
        $auth->removeChild($admin, $serverStatus);
        $auth->removeChild($admin, $monitorAllExam);
        $auth->removeChild($admin, $pingAllTicket);
        $auth->removeChild($admin, $serverLogs);
        $auth->removeChild($teacher, $monitorExam);
        $auth->removeChild($teacher, $pingTicket);

        $auth->remove($indexRole);
        $auth->remove($createRole);
        $auth->remove($viewRole);
        $auth->remove($updateRole);
        $auth->remove($deleteRole);
        $auth->remove($serverStatus);
        $auth->remove($monitorAllExam);
        $auth->remove($monitorExam);
        $auth->remove($pingTicket);
        $auth->remove($pingAllTicket);
        $auth->remove($serverLogs);

        $viewExam->description = yiit('permission', 'View/Monitor own exams');
        $viewAllExam->description = yiit('permission', 'View/Monitor all exams');
        $configServer->name = 'config/system';
        $admin->description = null;
        $teacher->description = null;
        $viewScreenshots->description = yiit('permission', 'View screenshots from a ticket');
        $snapScreenshots->description = yiit('permission', 'Create a live screenshot');
        
        $auth->update('exam/view', $viewExam); // rename "exam/view" description
        $auth->update('exam/view/all', $viewAllExam); // rename "exam/view/all" description
        $auth->update('server/config', $configServer); // rename "server/config" -> "config/system"
        $auth->update('admin', $admin); // remove "admin" description
        $auth->update('teacher', $teacher); // remove "teacher" description

        // re-add the "screeshot/view" and "screenshot/snap" permissions
        $auth->add($viewScreenshots);
        $auth->add($snapScreenshots);

        $auth->addChild($teacher, $viewScreenshots);
        $auth->addChild($teacher, $snapScreenshots);

        // remove "ticket/create" as child of "ticket/create/all"
        $auth->removeChild($createAllTicket, $createTicket);

        $this->dropColumn($this->eventStreamTable, 'watches');
        $this->dropColumn($this->daemonTable, 'memory');

        // flush the RBAC cache, else permissions might not be up-to-date
        $auth->invalidateCache();
        Yii::$app->cache->flush();
    }
}
