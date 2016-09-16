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
                $r = \Yii::$app->controller->id . '/' . \Yii::$app->controller->action->id;
                return $user->can($r);
            } elseif ($user->can($role)) {
                return true;
            }
        }
        return false;
    }
}
