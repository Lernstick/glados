<?php

use yii\helpers\Url;

class HomeCest
{
    public function ensureThatHomePageWorks(AcceptanceTester $I)
    {
        $I->amOnPage(Url::toRoute('/site/index'));        
        $I->see('GLaDOS');
        
        $I->seeLink('Login');
        $I->click('Login');
        $I->wait(2); // wait for page to be opened
        
        $I->see('Please fill out the following fields to login.');
    }
}
