<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\UserAuth;

/**
 * AuthLdapQueryForm is the model behind the ldap authentication form to query groups.
 */
class AuthLdapQueryForm extends \yii\base\Model
{
    public $username;
    public $password;
    public $auth_model;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['username', 'password'], 'safe'],
            // password is validated by authenticate()
            //['password', 'browse_ldap'],
        ];
    }

    /**
     * Test the connection.
     *
     * @return bool
     */
    public function browse_ldap_for_groups()
    {
        $err = 'yep';
        if ($this->auth_model->bindAd($this->username, $this->password)) {
            if ($this->auth_model->query_groups()) {
                //succ
            }
        }
        //$this->addError('password', $dbg);
		return true;
    }

    /**
     * Test the connection.
     * @return boolean
     */
    public function test()
    {
        if ($this->validate()) {
            return true;
        }
        return false;
    }

}