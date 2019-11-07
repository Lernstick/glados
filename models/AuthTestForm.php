<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\UserAuth;

/**
 * LoginForm is the model behind the login form.
 */
class AuthTestForm extends LoginForm
{
    public $username;
    public $password;
    public $method = 0;
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
            'login' => \Yii::t('auth', 'Username and password to test. Login credentials are not saved anywhere.'),
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
        $this->authModel->scenario = $this->authModel->class::SCENARIO_AUTH_TEST;
        if (!$this->authModel->authenticate($this->username, $this->password)) {
            $this->addError($attribute, \Yii::t('login', 'Authentication failed.'));
        }
    }

}
