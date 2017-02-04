<?php
namespace app\commands;

use Yii;
use yii\console\Controller;

class RbacController extends Controller
{

    /**
     * @inheritdoc
     */
    public function actionInit()
    {
        $auth = Yii::$app->authManager;

        /* first revoke all permissions and assignments */
        $auth->removeAll();

        /**
        * Exam permissions
        */
        $createExam = $auth->createPermission('exam/create');
        $createExam->description = 'Create an exam';
        $auth->add($createExam);

        $viewExam = $auth->createPermission('exam/view');
        $viewExam->description = 'View/Monitor own exams';
        $auth->add($viewExam);
        $viewAllExam = $auth->createPermission('exam/view/all');
        $viewAllExam->description = 'View/Monitor all exams';
        $auth->add($viewAllExam);

        $updateExam = $auth->createPermission('exam/update');
        $updateExam->description = 'Update own exams';
        $auth->add($updateExam);
        $updateAllExam = $auth->createPermission('exam/update/all');
        $updateAllExam->description = 'Update all exams';
        $auth->add($updateAllExam);

        $deleteExam = $auth->createPermission('exam/delete');
        $deleteExam->description = 'Delete own exams';
        $auth->add($deleteExam);
        $deleteAllExam = $auth->createPermission('exam/delete/all');
        $deleteAllExam->description = 'Delete all exams';
        $auth->add($deleteAllExam);

        $indexExam = $auth->createPermission('exam/index');
        $indexExam->description = 'Index own exams';
        $auth->add($indexExam);
        $indexAllExam = $auth->createPermission('exam/index/all');
        $indexAllExam->description = 'Index all exams';
        $auth->add($indexAllExam);

        /* Inheritation */
        $auth->addChild($indexAllExam, $indexExam);
        $auth->addChild($updateAllExam, $updateExam);
        $auth->addChild($viewAllExam, $viewExam);
        $auth->addChild($deleteAllExam, $deleteExam);

        /**
        * Ticket permissions
        */
        $indexTicket = $auth->createPermission('ticket/index');
        $indexTicket->description = 'Index tickets of own exams';
        $auth->add($indexTicket);
        $indexAllTicket = $auth->createPermission('ticket/index/all');
        $indexAllTicket->description = 'Index tickets of all exams';
        $auth->add($indexAllTicket);

        $viewTicket = $auth->createPermission('ticket/view');
        $viewTicket->description = 'View tickets of own exams';
        $auth->add($viewTicket);
        $viewAllTicket = $auth->createPermission('ticket/view/all');
        $viewAllTicket->description = 'View tickets of all exams';
        $auth->add($viewAllTicket);

        $createTicket = $auth->createPermission('ticket/create');
        $createTicket->description = 'Create a ticket for own exams';
        $auth->add($createTicket);
        $createAllTicket = $auth->createPermission('ticket/create/all');
        $createAllTicket->description = 'Create a ticket for all exams';
        $auth->add($createAllTicket);

        $updateTicket = $auth->createPermission('ticket/update');
        $updateTicket->description = 'Update tickets of own exams';
        $auth->add($updateTicket);
        $updateAllTicket = $auth->createPermission('ticket/update/all');
        $updateAllTicket->description = 'Update tickets of all exams';
        $auth->add($updateAllTicket);

        $deleteTicket = $auth->createPermission('ticket/delete');
        $deleteTicket->description = 'Delete tickets of own exams';
        $auth->add($deleteTicket);
        $deleteAllTicket = $auth->createPermission('ticket/delete/all');
        $deleteAllTicket->description = 'Delete tickets of all exams';
        $auth->add($deleteAllTicket);

        $backupTicket = $auth->createPermission('ticket/backup');
        $backupTicket->description = 'Backup clients of tickets of own exams';
        $auth->add($backupTicket);
        $backupAllTicket = $auth->createPermission('ticket/backup/all');
        $backupAllTicket->description = 'Backup clients of tickets of all exams';
        $auth->add($backupAllTicket);

        $restoreTicket = $auth->createPermission('ticket/restore');
        $restoreTicket->description = 'Restore data to clients of tickets of own exams';
        $auth->add($restoreTicket);
        $restoreAllTicket = $auth->createPermission('ticket/restore/all');
        $restoreAllTicket->description = 'Restore data to clients of tickets of all exams';
        $auth->add($restoreAllTicket);

        /* Inheritation */
        $auth->addChild($indexAllTicket, $indexTicket);
        $auth->addChild($viewAllTicket, $viewTicket);
        $auth->addChild($updateAllTicket, $updateTicket);
        $auth->addChild($deleteAllTicket, $deleteTicket);
        $auth->addChild($backupAllTicket, $backupTicket);
        $auth->addChild($restoreAllTicket, $restoreTicket);

        /**
        * Avtivity permissions
        */
        $indexActivity = $auth->createPermission('activity/index');
        $indexActivity->description = 'Index own activities';
        $auth->add($indexActivity);
        $indexAllActivity = $auth->createPermission('activity/index/all');
        $indexAllActivity->description = 'Index all activities';
        $auth->add($indexAllActivity);

        /* Inheritation */
        $auth->addChild($indexAllActivity, $indexActivity);

        /**
        * User management permissions
        */
        $indexUser = $auth->createPermission('user/index');
        $indexUser->description = 'Index all users';
        $auth->add($indexUser);

        $viewUser = $auth->createPermission('user/view');
        $viewUser->description = 'View own user';
        $auth->add($viewUser);
        $viewAllUser = $auth->createPermission('user/view/all');
        $viewAllUser->description = 'View all users';
        $auth->add($viewAllUser);

        $createUser = $auth->createPermission('user/create');
        $createUser->description = 'Create a user';
        $auth->add($createUser);

        $updateUser = $auth->createPermission('user/update');
        $updateUser->description = 'Update own user';
        $auth->add($updateUser);
        $updateAllUser = $auth->createPermission('user/update/all');
        $updateAllUser->description = 'Update all users';
        $auth->add($updateAllUser);

        $resetPWUser = $auth->createPermission('user/reset-password');
        $resetPWUser->description = 'Reset password for own user';
        $auth->add($resetPWUser);
        $resetPWAllUser = $auth->createPermission('user/reset-password/all');
        $resetPWAllUser->description = 'Reset password for all users';
        $auth->add($resetPWAllUser);

        $deleteUser = $auth->createPermission('user/delete');
        $deleteUser->description = 'Delete own user';
        $auth->add($deleteUser);
        $deleteAllUser = $auth->createPermission('user/delete/all');
        $deleteAllUser->description = 'Delete all users';
        $auth->add($deleteAllUser);

        /* Inheritation */
        $auth->addChild($viewAllUser, $viewUser);
        $auth->addChild($updateAllUser, $updateUser);
        $auth->addChild($resetPWAllUser, $resetPWUser);
        $auth->addChild($deleteAllUser, $deleteUser);

        /**
        * Daemon permissions
        */
        $indexDaemon = $auth->createPermission('daemon/index');
        $indexDaemon->description = 'Index all backup daemons';
        $auth->add($indexDaemon);

        $startDaemon = $auth->createPermission('daemon/create');
        $startDaemon->description = 'Start a new backup daemon';
        $auth->add($startDaemon);

        $viewDaemon = $auth->createPermission('daemon/view');
        $viewDaemon->description = 'View backup daemons';
        $auth->add($viewDaemon);

        $stopDaemon = $auth->createPermission('daemon/stop');
        $stopDaemon->description = 'Stop backup daemons';
        $auth->add($stopDaemon);

        $killDaemon = $auth->createPermission('daemon/kill');
        $killDaemon->description = 'Kill backup daemons';
        $auth->add($killDaemon);

        // Add "teacher" role
        $teacher = $auth->createRole('teacher');
        $auth->add($teacher);
        $auth->addChild($teacher, $createExam);
        $auth->addChild($teacher, $indexExam);
        $auth->addChild($teacher, $viewExam);
        $auth->addChild($teacher, $updateExam);
        $auth->addChild($teacher, $deleteExam);

        $auth->addChild($teacher, $createTicket);
        $auth->addChild($teacher, $indexTicket);
        $auth->addChild($teacher, $viewTicket);
        $auth->addChild($teacher, $updateTicket);
        $auth->addChild($teacher, $deleteTicket);
        $auth->addChild($teacher, $backupTicket);
        $auth->addChild($teacher, $restoreTicket);

        $auth->addChild($teacher, $indexActivity);

        $auth->addChild($teacher, $viewUser);
        $auth->addChild($teacher, $resetPWUser);

        $auth->addChild($teacher, $indexDaemon);
        $auth->addChild($teacher, $startDaemon);
        $auth->addChild($teacher, $viewDaemon);
        $auth->addChild($teacher, $stopDaemon);
        $auth->addChild($teacher, $killDaemon);

        // Add "admin" role and give this role the */all permissions
        $admin = $auth->createRole('admin');
        $auth->add($admin);
        $auth->addChild($admin, $indexAllExam);
        $auth->addChild($admin, $viewAllExam);
        $auth->addChild($admin, $updateAllExam);
        $auth->addChild($admin, $deleteAllExam);

        $auth->addChild($admin, $createAllTicket);
        $auth->addChild($admin, $indexAllTicket);
        $auth->addChild($admin, $viewAllTicket);
        $auth->addChild($admin, $updateAllTicket);
        $auth->addChild($admin, $deleteAllTicket);
        $auth->addChild($admin, $backupAllTicket);
        $auth->addChild($admin, $restoreAllTicket);

        $auth->addChild($admin, $indexAllActivity);

        $auth->addChild($admin, $indexUser);
        $auth->addChild($admin, $viewAllUser);
        $auth->addChild($admin, $createUser);
        $auth->addChild($admin, $updateAllUser);
        $auth->addChild($admin, $resetPWAllUser);
        $auth->addChild($admin, $deleteAllUser);

        // The "admin" role should also inherit all "teacher" permissions
        $auth->addChild($admin, $teacher);

        // Assign roles to users. 1 and 2 are IDs returned by IdentityInterface::getId()
        // usually implemented in your User model.
        #$auth->assign($teacher, 2);
        #$auth->assign($admin, 1);
    }
}
