<?php

namespace app\models;

use Yii;
use app\models\User;
use yii\web\IdentityInterface;

/**
 * UserAuth is the model behind the special Authentication.
 */
class UserAuth extends User implements IdentityInterface
{
    /**
     * Finds user by credentials
     *
     * @param  string $username
     * @param  string $password
     * @return static|null
     */
    public static function findByCredentials($username, $password)
    {

        foreach (Yii::$app->auth->methods as $key => $config) {

            $method = Yii::createObject($config);
            if ($method->authenticate($username, $password)) {

                // return existing user column from database
                if (($user = static::findOne(['identifier' => $method->identifier, 'type' => $method->type])) === null) {
                    // else create new user and return the record
                    $user = new User();
                    $user->type = $method->type;
                    $user->identifier = $method->identifier;
                }

                $user->role = $method->role;
                $user->username = $username;
                $user->scenario = User::SCENARIO_EXTERNAL;
                if ($user->save()) {
                    return $user;
                } else {
                    return null;
                }
            }
        }
        return null;
    }
}