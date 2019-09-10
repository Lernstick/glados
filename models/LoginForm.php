<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\UserAd;

/**
 * LoginForm is the model behind the login form.
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;

    private $_user = false;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['username', 'password'], 'required'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by authenticate()
            ['password', 'authenticate'],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function authenticate($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();

            if (!$user || !$user->validatePassword($this->password)) {
                // If the user could not be authenticated locally via database
                // try to authenticate via AD
                if (\Yii::$app->params['ad'] === true) {
                    $user = $this->getUserAd();

                    if (!$user) {
                        $this->addError($attribute, \Yii::t('login', Yii::$app->ad->name . ': Incorrect username or password.'));
                    }
                    return;
                }
                $this->addError($attribute, \Yii::t('login', 'Incorrect username or password.'));
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     * @return boolean whether the user is logged in successfully
     */
    public function login()
    {
        if ($this->validate()) {
            return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600*24*30 : 0);
        }
        return false;
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    public function getUser()
    {
        if ($this->_user === false) {
            $this->_user = User::findByUsername($this->username);
        }

        return $this->_user;
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    public function getUserAd()
    {
        if ($this->_user === null) {
            $this->_user = UserAd::findByCredentials($this->username, $this->password);
        }

        return $this->_user;
    }

}
