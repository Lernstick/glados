<?php

namespace app\components;

use yii\base\BootstrapInterface;

/**
 * LanguageSelector implements BootstrapInterface.
 *
 * Determine and set the preferred language
 * @see https://github.com/samdark/yii2-cookbook/blob/master/book/i18n-selecting-application-language.md
 */
class LanguageSelector implements BootstrapInterface
{
    public $supportedLanguages = [];

    public function bootstrap($app)
    {
        $preferredLanguage = $app->request->getPreferredLanguage($this->supportedLanguages);
        $app->language = $preferredLanguage;
    }
}