<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

/**
 * BaseController implements the RBAC check.
 */
class BaseController extends Controller
{

    /**
     * Returns the correct route for RBAC
     *
     * @return string the route
     */
    protected function getRbacRoute()
    {
        $controller_id = \Yii::$app->controller->canGetProperty('rbac_id')
            ? \Yii::$app->controller->rbac_id
            : \Yii::$app->controller->id;
        $action_id = \Yii::$app->controller->canGetProperty('action_id')
            ? \Yii::$app->controller->action_id
            : \Yii::$app->controller->action->id;
        return $controller_id . '/' . $action_id;
    }

    /**
     * Checks RBAC permission of a object model
     *
     * @param int $user_id the user id of the owner of the current object model
     * @return boolean whether access is allowed or not
     * @throws ForbiddenHttpException if the access control failed.
     */
    protected function checkRbac($user_id)
    {
        if (Yii::$app->user->can($this->rbacRoute . '/all') || $user_id == Yii::$app->user->id) {
            return true;
        } else {
            $p = $user_id == Yii::$app->user->id ? $this->rbacRoute : $this->rbacRoute . '/all';
            $model = Yii::$app->authManager->getPermission($p);
            throw new ForbiddenHttpException(\Yii::t('app', 'You are not allow to view this page. You need to have the following  permission: "{permission}".', [
                'permission' => $model === null ? $p : Yii::t('permission',  $model->description),
            ]));
            return false;
        }
    }
}
