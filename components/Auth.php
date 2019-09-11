<?php

namespace app\components;
 
use Yii;
use yii\base\Component;

/**
 * Auth represents all authentication methods.
 *
 * @property array $methods all methods in their corresponding order
 */
class Auth extends Component
{

    /* authentication type constants */
    const ACTIVE_DIRECTORY = 'ad';
    const LDAP = 'ldap';

    public $methods = [];
    public $name = 'auth';

}