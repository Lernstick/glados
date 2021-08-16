<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\components\AccessControl;
use yii\web\ForbiddenHttpException;
use yii\web\BadRequestHttpException;

/**
 * BaseController implements the RBAC check.
 */
class BaseController extends Controller
{

    /**
     * @var array list of actions where the current user should be tested against the owner of the
     * current object model, which is obtained by [[getOwner_id()]]
     */
    public $owner_actions = [];

    /**
     * @return int|null|false user id of the owner of the current object model in question.
     * * If the return value is a integer, the current object model is associated to the user
     *   with this id. If owner_id == current_user_id, the current user needs the permission
     *   "controller/action". If owner_id != current_user_id, the current user need the permission
     *   "controller/action/all".
     * * If the return value is null, an error will be thrown which states that the associated
     *   object model could not be determined. So a test whether the user has the right permission
     *   will not be possible.
     * * If the return value is false, then the access test will be successful if the user has
     *   the permission "controller/action".
     */
    public function getOwner_id()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['rbac'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Returns the correct route for RBAC.
     * Examples: ticket/view, ticket/view/all, server/status, ...
     *
     * @return string the RBAC route
     */
    protected function getRbacRoute()
    {
        $controller_id = \Yii::$app->controller->canGetProperty('rbac_id')
            ? \Yii::$app->controller->rbac_id
            : \Yii::$app->controller->id;
        $action_id = \Yii::$app->controller->canGetProperty('action_id')
            ? \Yii::$app->controller->action_id
            : \Yii::$app->controller->action->id;

        if (in_array($action_id, $this->owner_actions)) {
            $owner_id = \Yii::$app->controller->owner_id;
            if ($owner_id === false) {
                return $controller_id . '/' . $action_id;
            } else if ($owner_id === null) {
                return $controller_id . '/' . $action_id . '/controller->owner_id=null';
            } else if ($owner_id == \Yii::$app->user->id) {
                return $controller_id . '/' . $action_id;
            } else {
                return $controller_id . '/' . $action_id . '/all';
            }
        }

        return $controller_id . '/' . $action_id;
    }
}
