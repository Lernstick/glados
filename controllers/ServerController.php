<?php

namespace app\controllers;

use Yii;
use app\models\Config;
use app\models\ServerStatus;
use yii\web\NotFoundHttpException;
use app\models\Log;
use app\models\LogSearch;
use yii\helpers\ArrayHelper;

/**
 * ServerController implements the CRUD actions for Config model.
 */
class ServerController extends BaseController
{

    /**
     * @{inheritdoc}
     */
    public function getOwner_id()
    {
        return false;
    }

    /**
     * @{inheritdoc}
     */
    public function route_mapping()
    {
        return [
            'log' => 'server/logs',
            'downloadlog' => 'server/logs',
        ];
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
     * Displays the server configuration.
     *
     * @return mixed
     * @throws NotFoundHttpException if the config file cannot be found/parsed
     */
    public function actionConfig()
    {

        $model = Config::findOne([
            'avahiServiceFile' => '/etc/avahi/services/glados.service'
        ]);

        if ($model !== null) {
            return $this->render('config', [
                'model' => $model,
            ]);
        } else {
            throw new NotFoundHttpException('The avahi service file (/etc/avahi/services/glados.service) could not be parsed.');
        }
    }

    /**
     * Displays the server status.
     *
     * @return mixed
     * @throws NotFoundHttpException if the config file cannot be found/parsed
     */
    public function actionStatus()
    {
        $model = ServerStatus::find();
        $model->load(Yii::$app->request->get());

        return $this->render('status', [
            'model' => $model,
        ]);
    }

    /**
     * Lists all the server log files.
     * 
     * @return mixed
     */
    public function actionLogs()
    {
        $searchModel = new LogSearch();
        $dataProvider = $searchModel->search(['LogSearch' => ArrayHelper::merge(ArrayHelper::getvalue(Yii::$app->request->queryParams, 'LogSearch', []), ['token' => null])]);

        return $this->render('logs', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays the contents of the log file.
     * 
     * @return mixed
     * @throws NotFoundHttpException if the log file does not exist
     */
    public function actionLog($type, $date)
    {
        if (($model = Log::findOne([
            'token' => null,
            'type' => $type,
            'date' => $date,
        ])) !== null) {
            return $this->renderAjax('/log/view', [
                'model' => $model,
            ]);
        }

        throw new NotFoundHttpException(\Yii::t('app', 'The requested page does not exist.'));
    }

    /**
     * Downloads the log file.
     * 
     * @return mixed
     * @throws NotFoundHttpException if the log file does not exist
     */
    public function actionDownloadlog($type, $date)
    {
        if (($model = Log::findOne([
            'token' => null,
            'type' => $type,
            'date' => $date,
        ])) !== null) {
            return Yii::$app->response->sendContentAsFile(implode('', $model->contents), basename($model->path), [
                'inline' => false,
            ]);
        }

        throw new NotFoundHttpException(\Yii::t('app', 'The requested page does not exist.'));
    }
}
