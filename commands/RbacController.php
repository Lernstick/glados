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
        $createExam->description = yiit('permission', 'Create an exam');
        $auth->add($createExam);

        $viewExam = $auth->createPermission('exam/view');
        $viewExam->description = yiit('permission', 'View/Monitor own exams');
        $auth->add($viewExam);
        $viewAllExam = $auth->createPermission('exam/view/all');
        $viewAllExam->description = yiit('permission', 'View/Monitor all exams');
        $auth->add($viewAllExam);

        $updateExam = $auth->createPermission('exam/update');
        $updateExam->description = yiit('permission', 'Update own exams');
        $auth->add($updateExam);
        $updateAllExam = $auth->createPermission('exam/update/all');
        $updateAllExam->description = yiit('permission', 'Update all exams');
        $auth->add($updateAllExam);

        $deleteExam = $auth->createPermission('exam/delete');
        $deleteExam->description = yiit('permission', 'Delete own exams');
        $auth->add($deleteExam);
        $deleteAllExam = $auth->createPermission('exam/delete/all');
        $deleteAllExam->description = yiit('permission', 'Delete all exams');
        $auth->add($deleteAllExam);

        $indexExam = $auth->createPermission('exam/index');
        $indexExam->description = yiit('permission', 'Index own exams');
        $auth->add($indexExam);
        $indexAllExam = $auth->createPermission('exam/index/all');
        $indexAllExam->description = yiit('permission', 'Index all exams');
        $auth->add($indexAllExam);

        /*$submitResult = $auth->createPermission('result/submit');
        $submitResult->description = 'Submit results of own exams';
        $auth->add($submitResult);
        $submitAllResult = $auth->createPermission('result/submit/all');
        $submitAllResult->description = 'Submit results of all exams';
        $auth->add($submitAllResult);*/

        /* Inheritation */
        $auth->addChild($indexAllExam, $indexExam);
        $auth->addChild($updateAllExam, $updateExam);
        $auth->addChild($viewAllExam, $viewExam);
        $auth->addChild($deleteAllExam, $deleteExam);
        //$auth->addChild($submitAllResult, $submitResult);

        /**
        * Ticket permissions
        */
        $indexTicket = $auth->createPermission('ticket/index');
        $indexTicket->description = yiit('permission', 'Index tickets of own exams');
        $auth->add($indexTicket);
        $indexAllTicket = $auth->createPermission('ticket/index/all');
        $indexAllTicket->description = yiit('permission', 'Index tickets of all exams');
        $auth->add($indexAllTicket);

        $viewTicket = $auth->createPermission('ticket/view');
        $viewTicket->description = yiit('permission', 'View tickets of own exams');
        $auth->add($viewTicket);
        $viewAllTicket = $auth->createPermission('ticket/view/all');
        $viewAllTicket->description = yiit('permission', 'View tickets of all exams');
        $auth->add($viewAllTicket);

        $createTicket = $auth->createPermission('ticket/create');
        $createTicket->description = yiit('permission', 'Create a ticket for own exams');
        $auth->add($createTicket);
        $createAllTicket = $auth->createPermission('ticket/create/all');
        $createAllTicket->description = yiit('permission', 'Create a ticket for all exams');
        $auth->add($createAllTicket);

        $updateTicket = $auth->createPermission('ticket/update');
        $updateTicket->description = yiit('permission', 'Update tickets of own exams');
        $auth->add($updateTicket);
        $updateAllTicket = $auth->createPermission('ticket/update/all');
        $updateAllTicket->description = yiit('permission', 'Update tickets of all exams');
        $auth->add($updateAllTicket);

        $deleteTicket = $auth->createPermission('ticket/delete');
        $deleteTicket->description = yiit('permission', 'Delete tickets of own exams');
        $auth->add($deleteTicket);
        $deleteAllTicket = $auth->createPermission('ticket/delete/all');
        $deleteAllTicket->description = yiit('permission', 'Delete tickets of all exams');
        $auth->add($deleteAllTicket);

        $backupTicket = $auth->createPermission('ticket/backup');
        $backupTicket->description = yiit('permission', 'Backup clients of tickets of own exams');
        $auth->add($backupTicket);
        $backupAllTicket = $auth->createPermission('ticket/backup/all');
        $backupAllTicket->description = yiit('permission', 'Backup clients of tickets of all exams');
        $auth->add($backupAllTicket);

        $restoreTicket = $auth->createPermission('ticket/restore');
        $restoreTicket->description = yiit('permission', 'Restore data to clients of tickets of own exams');
        $auth->add($restoreTicket);
        $restoreAllTicket = $auth->createPermission('ticket/restore/all');
        $restoreAllTicket->description = yiit('permission', 'Restore data to clients of tickets of all exams');
        $auth->add($restoreAllTicket);

        /* Inheritation */
        $auth->addChild($indexAllTicket, $indexTicket);
        $auth->addChild($viewAllTicket, $viewTicket);
        $auth->addChild($updateAllTicket, $updateTicket);
        $auth->addChild($deleteAllTicket, $deleteTicket);
        $auth->addChild($backupAllTicket, $backupTicket);
        $auth->addChild($restoreAllTicket, $restoreTicket);

        /**
        * Screeshot permissions
        */
        $viewScreenshots = $auth->createPermission('screenshot/view');
        $viewScreenshots->description = yiit('permission', 'View screenshots from a ticket');
        $auth->add($viewScreenshots);
        $snapScreenshots = $auth->createPermission('screenshot/snap');
        $snapScreenshots->description = yiit('permission', 'Create a live screenshot');
        $auth->add($snapScreenshots);

        /**
        * Avtivity permissions
        */
        $indexActivity = $auth->createPermission('activity/index');
        $indexActivity->description = yiit('permission', 'Index own activities');
        $auth->add($indexActivity);
        $indexAllActivity = $auth->createPermission('activity/index/all');
        $indexAllActivity->description = yiit('permission', 'Index all activities');
        $auth->add($indexAllActivity);

        /* Inheritation */
        $auth->addChild($indexAllActivity, $indexActivity);

        /**
        * User management permissions
        */
        $indexUser = $auth->createPermission('user/index');
        $indexUser->description = yiit('permission', 'Index all users');
        $auth->add($indexUser);

        $viewUser = $auth->createPermission('user/view');
        $viewUser->description = yiit('permission', 'View own user');
        $auth->add($viewUser);
        $viewAllUser = $auth->createPermission('user/view/all');
        $viewAllUser->description = yiit('permission', 'View all users');
        $auth->add($viewAllUser);

        $createUser = $auth->createPermission('user/create');
        $createUser->description = yiit('permission', 'Create a user');
        $auth->add($createUser);

        $updateUser = $auth->createPermission('user/update');
        $updateUser->description = yiit('permission', 'Update own user');
        $auth->add($updateUser);
        $updateAllUser = $auth->createPermission('user/update/all');
        $updateAllUser->description = yiit('permission', 'Update all users');
        $auth->add($updateAllUser);

        $resetPWUser = $auth->createPermission('user/reset-password');
        $resetPWUser->description = yiit('permission', 'Reset password for own user');
        $auth->add($resetPWUser);
        $resetPWAllUser = $auth->createPermission('user/reset-password/all');
        $resetPWAllUser->description = yiit('permission', 'Reset password for all users');
        $auth->add($resetPWAllUser);

        $deleteUser = $auth->createPermission('user/delete');
        $deleteUser->description = yiit('permission', 'Delete own user');
        $auth->add($deleteUser);
        $deleteAllUser = $auth->createPermission('user/delete/all');
        $deleteAllUser->description = yiit('permission', 'Delete all users');
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
        $indexDaemon->description = yiit('permission', 'Index all backup daemons');
        $auth->add($indexDaemon);

        $startDaemon = $auth->createPermission('daemon/create');
        $startDaemon->description = yiit('permission', 'Start a new backup daemon');
        $auth->add($startDaemon);

        $viewDaemon = $auth->createPermission('daemon/view');
        $viewDaemon->description = yiit('permission', 'View backup daemons');
        $auth->add($viewDaemon);

        $stopDaemon = $auth->createPermission('daemon/stop');
        $stopDaemon->description = yiit('permission', 'Stop backup daemons');
        $auth->add($stopDaemon);

        $killDaemon = $auth->createPermission('daemon/kill');
        $killDaemon->description = yiit('permission', 'Kill backup daemons');
        $auth->add($killDaemon);

        /**
        * Configuation permissions
        */
        $viewSystemConfig = $auth->createPermission('config/system');
        $viewSystemConfig->description = yiit('permission', 'View the system configuration');
        $auth->add($viewSystemConfig);

        // Add "teacher" role
        $teacher = $auth->createRole('teacher');
        $auth->add($teacher);
        $auth->addChild($teacher, $createExam);
        $auth->addChild($teacher, $indexExam);
        $auth->addChild($teacher, $viewExam);
        $auth->addChild($teacher, $updateExam);
        $auth->addChild($teacher, $deleteExam);
        //$auth->addChild($teacher, $submitResult);

        $auth->addChild($teacher, $createTicket);
        $auth->addChild($teacher, $indexTicket);
        $auth->addChild($teacher, $viewTicket);
        $auth->addChild($teacher, $updateTicket);
        $auth->addChild($teacher, $deleteTicket);
        $auth->addChild($teacher, $backupTicket);
        $auth->addChild($teacher, $restoreTicket);

        $auth->addChild($teacher, $viewScreenshots);
        $auth->addChild($teacher, $snapScreenshots);

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
        //$auth->addChild($admin, $submitAllResult);

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

        $auth->addChild($admin, $viewSystemConfig);

        // The "admin" role should also inherit all "teacher" permissions
        $auth->addChild($admin, $teacher);

        // Assign roles to users. 1 and 2 are IDs returned by IdentityInterface::getId()
        // usually implemented in your User model.
        #$auth->assign($teacher, 2);
        #$auth->assign($admin, 1);
    }
}
