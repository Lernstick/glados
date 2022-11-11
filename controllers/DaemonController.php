<?php

namespace app\controllers;

use Yii;
use app\models\Daemon;
use app\models\DaemonSearch;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\models\Setting;

/**
 * DaemonController implements the CRUD actions for Daemon model.
 */
class DaemonController extends BaseController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'stop' => ['POST'],
                    'kill' => ['POST'],
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
     * Lists all Daemon models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new DaemonSearch();
        $runningDaemons = $searchModel->search([])->totalCount;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $minDaemons = Setting::findByKey('minDaemons');
        $maxDaemons = Setting::findByKey('maxDaemons');

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('index', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
                'runningDaemons' => $runningDaemons,
                'minDaemons' => $minDaemons,
                'maxDaemons' => $maxDaemons,
            ]);
        } else {
            return $this->render('index', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
                'runningDaemons' => $runningDaemons,
                'minDaemons' => $minDaemons,
                'maxDaemons' => $maxDaemons,
            ]);
        }

    }

    /**
     * Creates a new Daemon model.
     * If request is not Ajax, the browser will be redirected to the 'index' page.
     * @return mixed
     */
    public function actionCreate($type = 'backup')
    {

        $model = new Daemon();
        switch ($type) {
            case 'daemon':
                $model->startDaemon();
                break;
            case 'backup':
                $model->startBackup();
                break;
            case 'analyze':
                $model->startAnalyzer();
                break;
            case 'download':
                $model->startDownload();
                break;                
        }

        if (Yii::$app->request->isAjax) {
            return $this->runAction('index');
        } else {
            return $this->redirect(['index']);
        }

    }

    /**
     * Deletes a Daemon model.
     * If request is not Ajax, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionStop($id)
    {
        if ($id == 'ALL') {
            foreach(Daemon::find()->all() as $model) {
                $model->stop();
            }
        } else if (($model = Daemon::findOne($id)) !== null) {
            $model->stop();
        }

        if (Yii::$app->request->isAjax) {
            return $this->runAction('index');
        } else {
            return $this->redirect(['index']);
        }

    }

    /**
     * Kills a Daemon model.
     * If request is not Ajax, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionKill($id)
    {

        if (($model = Daemon::findOne($id)) !== null) {
            $model->kill();
        }

        if (Yii::$app->request->isAjax) {
            return $this->runAction('index');
        } else {
            return $this->redirect(['index']);
        }

    }

    /**
     * Finds the Daemon model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Daemon the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Daemon::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException(\Yii::t('app', 'The requested page does not exist.'));
        }
    }

}
