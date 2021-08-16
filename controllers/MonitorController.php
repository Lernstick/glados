<?php

namespace app\controllers;

use Yii;
use app\models\Exam;
use app\models\ExamSearch;
use app\models\Ticket;
use app\models\TicketSearch;
use app\models\Issue;
use app\models\IssueSearch;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\VerbFilter;

/**
 * MonitorController.
 */
class MonitorController extends BaseController
{

    /**
     * @inheritdoc
     */
    public $owner_actions = ['view', 'single'];

    /**
     * @var string Fake the controller id for the RBAC system
     */
    public $rbac_id = 'exam';

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
    public function getOwner_id()
    {
        $id = Yii::$app->request->get('id');
        if ($this->action->id == 'view') {
            if (($model = Exam::findOne($id)) !== null) {
                return $model->user_id;
            } else {
                return false;
            }
        } else if ($this->action->id == 'single') {
            if (($model = Ticket::findOne($id)) !== null) {
                return $model->exam->user_id;
            } else {
                return null;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
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
     * Monitors a single Exam model.
     * @param integer $id exam id
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

            $exam = $this->findModel($id);

            $params["TicketSearch"]["exam_id"] = $exam->id;
            $params["TicketSearch"]["state"] = Ticket::STATE_RUNNING;
            $params["IssueSearch"]["exam_id"] = $exam->id;
            $params["IssueSearch"]["solved"] = false;
            $params["IssueSearch"]["ticket_state"] = Ticket::STATE_RUNNING;

            $searchModel = new TicketSearch();
            $dataProvider = $searchModel->search($params);
            $dataProvider->pagination->pageParam = 'mon-page';
            $dataProvider->pagination->pageSize = 12;
            $dataProvider->setSort(['defaultOrder' => ['start' => SORT_ASC]]); // sort by start date 

            $issueSearchModel = new IssueSearch();
            $issueDataProvider = $issueSearchModel->search($params);
            $issueDataProvider->pagination->pageParam = 'issue-page';
            $issueDataProvider->pagination->pageSize = 10;

            return $this->render('monitor', [
                'exam' => $exam,
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
                'issueSearchModel' => $issueSearchModel,
                'issueDataProvider' => $issueDataProvider,
            ]);
        }
    }

    /**
     * Monitors a single Ticket model.
     * @param integer $id ticket id
     * @return mixed
     */
    public function actionSingle($id)
    {
        $model = $this->findTicket($id);
        return $this->renderAjax('_live_overview_item', [
            'model' => $model,
            'large' => true,
        ]);
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
            return $model;
        }
        throw new NotFoundHttpException(\Yii::t('app', 'The requested page does not exist.'));
    }

    /**
     * Finds the Ticket model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param integer $id
     * @return Ticket the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findTicket($id)
    {
        if (($model = Ticket::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(\Yii::t('app', 'The requested page does not exist.'));
    }

}