<?php

namespace app\controllers;

use Yii;
use app\models\Exam;
use app\models\ExamSearch;
use app\models\Ticket;
use app\models\TicketSearch;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\VerbFilter;
use app\components\AccessRule;

/**
 * MonitorController.
 */
class MonitorController extends BaseController
{

    /**
     * @var string Fake the controller id for the RBAC system
     */
    public $rbac_id = 'ticket';

    /**
     * @var string Fake the action id for the RBAC system
     */
    public function getAction_id ()
    {
        return 'view';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
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
     * Monitors a single Exam model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id = null)
    {

        if ($id === null) {
            $model = new Exam();
            $searchModel = new ExamSearch();

            return $this->render('monitor_s1', [
                'model' => $model,
                'searchModel' => $searchModel,
            ]);

        } else {

            $model = $this->findModel($id);

            $params["TicketSearch"]["exam_id"] = $model->id;
            $params["TicketSearch"]["state"] = Ticket::STATE_RUNNING;

            $searchModel = new TicketSearch();
            $dataProvider = $searchModel->search($params);
            $dataProvider->pagination->pageParam = 'mon-page';
            $dataProvider->pagination->pageSize = 12;
            // sort by start date 
            $dataProvider->setSort(['defaultOrder' => ['start' => SORT_ASC]]);

            return $this->render('monitor', [
                'model' => $model,
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
            ]);
        }
    }

    /**
     * Finds the Exam model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Exam the loaded model
     * @throws NotFoundHttpException if the model cannot be found.
     * @throws ForbiddenHttpException if access control failed.
     */
    protected function findModel($id)
    {
        if (($model = Exam::findOne($id)) !== null) {
            if ($this->checkRbac($model->user_id)) {
                return $model;
            }
        }
        throw new NotFoundHttpException(\Yii::t('app', 'The requested page does not exist.'));
    }

}