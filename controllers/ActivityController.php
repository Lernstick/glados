<?php

namespace app\controllers;

use Yii;
use app\models\Activity;
use app\models\ActivitySearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\CookieCollection;
use yii\db\Expression;
use app\models\EventItem;
use app\models\EventStream;
use app\components\AccessRule;
use app\models\User;

/**
 * ActivityController implements the CRUD actions for Activity model.
 */
class ActivityController extends Controller
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
                'class' => \yii\filters\AccessControl::className(),
                'ruleConfig' => [
                    'class' => AccessRule::className(),
                ],
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
     * @return mixed
     */
    public function actionIndex()
    {

        $this->on(self::EVENT_AFTER_ACTION, function($this){
            $model = new ActivitySearch();
            $model->lastvisited = 'now';
        });

        $searchModel = new ActivitySearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
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
