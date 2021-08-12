<?php

namespace app\controllers;

use Yii;
use app\models\Activity;
use app\models\ActivitySearch;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\CookieCollection;
use yii\db\Expression;
use app\models\User;

/**
 * ActivityController implements the CRUD actions for Activity model.
 */
class ActivityController extends BaseController
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
            'access' => [
                'class' => \app\components\AccessControl::className(),
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
     * Lists all Activity models.
     *
     * @param string $mode mode
     * @param string $attr attribute to make a list of
     * @param string $q query
     * @param int $page page
     * @param int $per_page entries per page
     * @return mixed
     */
    public function actionIndex($mode = null, $attr = null, $q = null, $page = 1, $per_page = 10)
    {

        if ($mode === null) {
            $this->on(self::EVENT_AFTER_ACTION, function(){
                $model = new ActivitySearch();
                $model->lastvisited = 'now';
            });

            $searchModel = new ActivitySearch();
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

            return $this->render('index', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
            ]);
        } else if ($mode == 'list') {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            $out = [];
            if (!is_null($attr)) {
                $searchModel = new ActivitySearch();
                if ($attr == 'description') {
                    $out = $searchModel->selectList('description', $q, $page, $per_page);
                }
            }
            return $out;
        }

    }


    public function actionNew()
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        //$cookies = Yii::$app->request->cookies;
        //$lastvisited = $cookies->getValue('lastvisited');
        $user = User::findOne(\Yii::$app->user->id);
        $lastvisited = $user->activities_last_visited;

        $query = Activity::find()->where(['>', 'date', $lastvisited]);
        Yii::$app->user->can('activity/index/all') ?: $query->own();

        $new = $query->count();

        return [ 'new_activities' => $new ];
    }

}
