<?php

namespace app\components;

class AccessRule extends \yii\filters\AccessRule
{

    /*
     * @inheritdoc
     */
    protected function matchRole($user)
    {
        if (empty($this->roles)) {
            return true;
        }

        foreach ($this->roles as $role) {
            if ($role === '?') {
                if ($user->getIsGuest()) {
                    return true;
                }
            } elseif ($role === '@') {
                if (!$user->getIsGuest()) {
                    return true;
                }
            } elseif ($role === 'rbac') {
                return $user->can(\Yii::$app->controller->rbacRoute);
            } elseif ($user->can($role)) {
                return true;
            }
        }
        return false;
    }

}
