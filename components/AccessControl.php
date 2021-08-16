<?php

namespace app\components;

use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

class AccessControl extends \yii\filters\AccessControl
{

    /**
     * @inheritdoc
     */
    public $ruleConfig = ['class' => 'app\components\AccessRule'];

    /**
     * @inheritdoc
     * 
     * @throws BadRequestHttpException if the owner of the curren object model cannot be determined.
     * @throws ForbiddenHttpException if the access control failed.
     */
    protected function denyAccess($user)
    {
        if ($user !== false && $user->getIsGuest()) {
            $user->loginRequired();
        } else {
            $controller = $this->owner;
            $owner_id = $controller->owner_id;
            $p = $controller->rbacRoute;

            if ($owner_id === null && in_array($controller->action->id, $controller->owner_actions)) {
                throw new BadRequestHttpException(\Yii::t('app', 'Unable to determine the owner of the current object model ({route}).', [
                    'route' => $p,
                ]));
            }
            $permission = \Yii::$app->authManager->getPermission($p);
            throw new ForbiddenHttpException(\Yii::t('app', 'You are not allowed to view this page. You need to have the following permission: "{permission} ({short})".', [
                'permission' => $permission === null ? $p : \Yii::t('permission',  $permission->description),
                'short' => $p,
            ]));
        }
    }
}
