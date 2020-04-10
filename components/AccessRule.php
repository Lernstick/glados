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
                $controller_id = \Yii::$app->controller->canGetProperty('rbac_id')
                    ? \Yii::$app->controller->rbac_id
                    : \Yii::$app->controller->id;
                $action_id = \Yii::$app->controller->canGetProperty('action_id')
                    ? \Yii::$app->controller->action_id
                    : \Yii::$app->controller->action->id;
                $r = $controller_id . '/' . $action_id;
                return $user->can($r);
            } elseif ($user->can($role)) {
                return true;
            }
        }
        return false;
    }
}
