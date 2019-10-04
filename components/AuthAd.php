<?php

namespace app\components;
 
use Yii;
use yii\base\Component;
use app\models\User;
use yii\base\InvalidConfigException;

/**
 * AuthAd represents a simple connection to a Active Directory via LDAP.
 *
 * @inheritdoc
 */
class AuthAd extends AuthAdExtended
{

    /**
     * @inheritdoc
     */
    public $class = 'app\components\AuthAd';

    /**
     * @inheritdoc
     */
    public $type = \app\models\Auth::AUTH_ACTIVE_DIRECTORY;

    /**
     * @inheritdoc
     */
    public $typeName = 'Active Directory';

    /**
     * @inheritdoc
     */
    public $view = 'view_ad';

    /**
     * @inheritdoc
     */
    public $form = '_form_ad';

}
