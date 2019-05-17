<?php

use yii\helpers\Url;
use app\tests\fixtures\UserFixture;

class AdminCest
{
    public function _fixtures()
    {
        return [
            'users' => [
                'class' => UserFixture::className(),
                'dataFile' => codecept_data_dir() . 'user.php'
            ],
        ];
    }

    /**
    * @example { "name": "user2", "pw": "user2", "role": "admin" }
    */
    public function addUser(AcceptanceTester $I, \Codeception\Example $example)
    {
        $I->login('admin', 'admin');

        $I->seeLink('Actions');
        $I->click('Actions');
        $I->waitForText('Create User', 10);
        $I->seeLink('Create User');
        $I->click('Create User');
        $I->waitForText('Create User', 10, 'h1');

        $this->fillUserForm($I, $example['name'], $example['role'], $example['pw'], $example['pw']);
        $I->click('button[type=submit]');
        $I->waitForText('Username ' . $example['name'], 10);

    }

    private function fillUserForm(AcceptanceTester $I, $name, $role, $pw, $pw2)
    {
        $I->fillField('input[name="User[username]"]', $name);
        $I->selectOption('select[name="User[role]"]', $role);
        $I->fillField('input[name="User[password]"]', $pw);
        $I->fillField('input[name="User[password_repeat]"]', $pw2);
    }

}
