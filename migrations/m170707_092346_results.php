<?php

use yii\db\Migration;

class m170707_092346_results extends Migration
{
    public $ticketTable = 'ticket';

    public function safeUp()
    {
        $this->addColumn($this->ticketTable, 'result', $this->string(255)->defaultValue(Null));

        $auth = Yii::$app->authManager;

        $submitResult = $auth->createPermission('result/submit');
        $submitResult->description = 'Submit results of own exams';
        $auth->add($submitResult);
        $submitAllResult = $auth->createPermission('result/submit/all');
        $submitAllResult->description = 'Submit results of all exams';
        $auth->add($submitAllResult);

        $generateResult = $auth->createPermission('result/generate');
        $generateResult->description = 'Generate result zip files of own exams';
        $auth->add($generateResult);
        $generateAllResult = $auth->createPermission('result/generate/all');
        $generateAllResult->description = 'Generate result zip files of all exams';
        $auth->add($generateAllResult);

        /* Inheritation */
        $auth->addChild($submitAllResult, $submitResult);
        $auth->addChild($generateAllResult, $generateResult);

        $teacher = $auth->getRole('teacher');
        $auth->addChild($teacher, $submitResult);
        $auth->addChild($teacher, $generateResult);

        $admin = $auth->getRole('admin');
        $auth->addChild($admin, $submitAllResult);
        $auth->addChild($admin, $generateAllResult);
    }

    public function safeDown()
    {
        $this->dropColumn($this->ticketTable, 'result');

        $auth = Yii::$app->authManager;

        $submitResult = $auth->getPermission('result/submit');
        $submitAllResult = $auth->getPermission('result/submit/all');
        $generateResult = $auth->getPermission('result/generate');
        $generateAllResult = $auth->getPermission('result/generate/all');

        $auth->removeChild($submitAllResult, $submitResult);
        $auth->removeChild($generateAllResult, $generateResult);

        $teacher = $auth->getRole('teacher');
        $auth->removeChild($teacher, $submitResult);
        $auth->removeChild($teacher, $generateResult);

        $admin = $auth->getRole('admin');
        $auth->removeChild($admin, $submitAllResult);
        $auth->removeChild($admin, $generateAllResult);

        $auth->remove($submitResult);
        $auth->remove($submitAllResult);
        $auth->remove($generateResult);
        $auth->remove($generateAllResult);

    }
}
