<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\UserAuth;

/**
 * AuthTestForm is the model behind the test login form.
 */
class AuthTestForm extends LoginForm
{
    /**
     * @var string Username to test.
     */
    public $username;

    /**
     * @var string Password to test.
     */
    public $password;

    /**
     * @var string Authentication method id to test.
     */
    public $method = 0;

    /**
     * @var Auth Authentication object.
     */
    public $authModel;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->authModel = Auth::findOne(0);
    }

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['username', 'password', 'method'], 'required'],
            // password is validated by authenticate()
            ['password', 'authenticate'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'login' =>\Yii::t('auth', 'Test Credentials'),
            'username' => \Yii::t('auth', 'Username'),
            'password' => \Yii::t('auth', 'Password'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'login' => \Yii::t('auth', 'Username and password to test login for. <i>Login credentials are not saved anywhere.</i>'),
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
        $this->authModel = Auth::findOne($this->method);
        if (is_object($this->authModel)) {
            $this->authModel->scenario = $this->authModel->class::SCENARIO_AUTH_TEST;
            if ($this->authModel->authenticate($this->username, $this->password)) {
                return;
            }
        } else {
            $this->authModel = new Auth();
            $this->authModel->error = \Yii::t('auth', 'Authentication method not existing.');
        }
        $this->addError($attribute, \Yii::t('login', 'Authentication failed.'));
    }

}
