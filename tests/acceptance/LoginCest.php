<?php

use yii\helpers\Url;

class LoginCest
{
    public function ensureThatLoginWorksWithAdmin(AcceptanceTester $I)
    {
        $I->amOnPage(Url::toRoute('/site/login'));
        $I->see('Login', 'h1');

        $I->amGoingTo('try to login with correct admin credentials');
        $I->fillField('input[name="LoginForm[username]"]', 'admin');
        $I->fillField('input[name="LoginForm[password]"]', 'admin');
        $I->click('login-button');
        
        $I->expectTo('see admin panel');
        $I->waitForText('Logout', 10);
        $I->see('Config');
        $I->see('Profile');
        $I->see('Users');
        $I->click('Profile');
        
        $I->expectTo('see permissions');
        $I->waitForText('Role admin');

        $I->click('Logout');
        $I->waitForText('Login', 10);
    }

    public function ensureThatLoginWorksWithTeacher(AcceptanceTester $I)
    {
        $I->amOnPage(Url::toRoute('/site/login'));
        $I->see('Login', 'h1');

        $I->amGoingTo('try to login with correct teacher credentials');
        $I->fillField('input[name="LoginForm[username]"]', 'teacher');
        $I->fillField('input[name="LoginForm[password]"]', 'teacher');
        $I->click('login-button');
        
        $I->expectTo('see teacher panel');
        $I->waitForText('Logout', 10);
        $I->see('Profile');
        $I->dontSee('Config');
        $I->dontSee('Users');
        $I->click('Profile');
        
        $I->expectTo('see permissions');
        $I->waitForText('Role teacher');

        $I->click('Logout');
        $I->waitForText('Login', 10);
    }

}
