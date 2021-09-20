<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\components\AccessControl;
use yii\web\ForbiddenHttpException;
use yii\web\BadRequestHttpException;
use yii\helpers\StringHelper;

/**
 * BaseController implements the RBAC check.
 */
class BaseController extends Controller
{

    /**
     * @var array list of actions where the current user should be tested against the owner of the
     * current object model, which is obtained by [[getOwner_id()]]. The list of actions is compared
     * to the returnvalue of [[rbacRoute]], see [[getRbacRoute()]] below. If an action or controller
     * id is faked using [[route_mapping()]], the array of [[owner_action]] should contain the new 
     * action id.
     */
    public $owner_actions = [];

    /**
     * @return int|null|false user id of the owner of the current object model in question.
     * 1) If the return value is a integer, the current object model is associated to the user
     *    with this id. If owner_id == current_user_id, the current user needs the permission
     *    "controller/action". If owner_id != current_user_id, the current user needs the permission
     *    "controller/action/all".
     * 2) If the return value is null, an error will be thrown which states that the associated
     *    object model could not be determined. By this, a test whether the user has the right 
     *    permission will not be possible.
     * 3) If the return value is false, then the access test will be successful if the user has
     *    the permission "controller/action".
     */
    public function getOwner_id()
    {
        return null;
    }

    /**
     * @var array Mapping of the RBAC route to a fake route, with which the RBAC access control should
     * be evaluated. Examples:
     * 
     * 1) ['*' => 'controller/action'] // map all actions to 'controller/action'
     * 2) ['actionA' => 'controller/actionB'] // map only 'actionA' to 'controller/actionB', all others stay
     * 3) ['view*' => 'controller/actionC'] // map only actions matching view* to 'controller/actionC'
     * 
     */
    public function route_mapping ()
    {
        return [];
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

        $route = \Yii::$app->controller->id . '/' . \Yii::$app->controller->action->id;
        foreach($this->route_mapping() as $pattern => $fake_route) {
            if (StringHelper::matchWildcard($pattern, \Yii::$app->controller->action->id)) {
                $route = $fake_route;
            }
        }
        list($controller_id, $action_id) = explode('/', $route, 2);

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
