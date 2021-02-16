<?php

namespace app\models;

use Yii;
use app\models\User;
use app\models\Auth;
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

        // sorting of the methods array according to order
        uasort(Yii::$app->auth->methods, function ($a, $b) {
           return $a['order'] - $b['order'];
        });

        foreach (Yii::$app->auth->methods as $key => $config) {
            $method = Auth::findOne($key); #Yii::createObject($config);
            if ($method !== null && $method->authenticate($username, $password)) {

                // return existing user column from database
                if (($user = static::findOne(['identifier' => $method->identifier, 'type' => $key])) === null) {
                    // else create new user and return the record
                    $user = new User();
                    $user->type = $key;
                    $user->identifier = $method->identifier;
                }

                $user->role = $method->role;
                $user->username = $username;
                $user->scenario = User::SCENARIO_EXTERNAL;
                if ($user->save()) {
                    return $user;
                } else {
                    Yii::debug('Auth: save() failed: ' . json_encode($user->getErrors()), __METHOD__);
                    return null;
                }
            }
        }
        return null;
    }
}
