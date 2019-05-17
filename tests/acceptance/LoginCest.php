<?php

use yii\helpers\Url;
use app\tests\fixtures\UserFixture;

class LoginCest
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
     * @example { "name": "admin", "pw": "admin", "role": "admin" }
     * @example { "name": "teacher", "pw": "teacher", "role": "teacher" }
     */
    public function testAuthentication(AcceptanceTester $I, \Codeception\Example $example)
    {
        $I->login($example['name'], $example['pw']);

        $I->expectTo('see ' . $example['role'] . ' panel');
        $I->see('Profile');

        if ($example['role'] == "admin") {
            $I->see('Config');
            $I->see('Users');
        } else if ($example['role'] == "teacher") {
            $I->dontSee('Config');
            $I->dontSee('Users');
        }

        $I->click('Profile');
        
        $I->expectTo('see permissions');
        $I->waitForText('Role ' . $example['role']);

        $I->logout();
    }

}
