<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\TicketSearch;
use app\models\Activity;
use app\models\ExamSearch;

class SiteController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex()
    {
        $activity = new Activity();
        $new_activities = $activity->newActivities();
        $new_activities = $new_activities == '' ? 0 : $new_activities;

        $searchModel = new TicketSearch();
        $running_exams = $searchModel->getRunningTickets()->count();
        $completed_exams = $searchModel->getCompletedTickets()->count();
        $total_tickets = $searchModel->getTotalTickets()->count();
        $total_duation = $searchModel->getTotalDuration();
        $tot = intval($completed_exams) + intval($running_exams);
        $average_duration = $tot == 0 ? 0 : intval($total_duation) / $tot;

        $searchModel = new ExamSearch();
        $total_exams = $searchModel->getTotalExams()->count();

        return $this->render('index', [
            'new_activities' => intval($new_activities),
            'running_exams' => intval($running_exams),
            'total_exams' => intval($total_exams),
            'completed_exams' => intval($completed_exams),
            'total_tickets' => intval($total_tickets),
            'total_duation' => intval($total_duation),
            'average_duration' => intval($average_duration),
        ]);
    }

    public function actionLogin()
    {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }
}
