<?php

namespace app\models;

use Yii;
use app\models\User;
use yii\web\IdentityInterface;

/**
 * UserAd is the model behind the Active Directory Authentication.
 */
class UserAd extends User implements IdentityInterface
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
        if (Yii::$app->ad->authenticate($username, $password)) {

            // return existing user column from database
            if (($user = static::findOne(['identifier' => Yii::$app->ad->identifier, 'type' => 'ad'])) === null) {
                // else create new user and return the record
                $user = new User();
                $user->type = "ad";
                $user->identifier = Yii::$app->ad->identifier;
            }

            $user->role = Yii::$app->ad->role;
            $user->username = $username;
            $user->scenario = User::SCENARIO_EXTERNAL;
            if ($user->save()) {
                return $user;
            } else {
                return null;
            }
        }
        return null;
    }
}