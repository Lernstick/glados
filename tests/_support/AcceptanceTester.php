<?php

use yii\helpers\Url;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

   /**
    * Define custom actions here
    */
    public function login($name, $password)
    {
        $I = $this;

        $I->amOnPage(Url::toRoute('/site/login'));
        $I->see('Login', 'h1');
        $I->amGoingTo('try to login with correct credentials');
        $I->fillField('input[name="LoginForm[username]"]', $name);
        $I->fillField('input[name="LoginForm[password]"]', $password);
        $I->click('login-button');
        $I->waitForText('Logout', 10);
    }

    public function logout()
    {
        $I = $this;

        $I->seeLink('Logout');
        $I->click('Logout');
        $I->waitForText('Login');
    }

}
