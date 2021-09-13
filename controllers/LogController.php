<?php

namespace app\controllers;

use Yii;
use app\models\Log;
use app\models\LogSearch;
use app\models\Ticket;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * LogController implements the view actions for Log model.
 */
class LogController extends BaseController
{

    /**
     * @inheritdoc
     */
    public $owner_actions = ['view', 'download'];

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
    public function getOwner_id()
    {
        $token = Yii::$app->request->get('token');
        if (($model = Ticket::findOne(['token' => $token])) !== null) {
            return $model->exam->user_id;
        } else {
            return null;
        }
    }

    /*
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
     * Lists all Log models of a certain ticket.
     * @param int $ticket_id ticket id
     * @return mixed
     */
    public function actionIndex($ticket_id)
    {
        return $this->redirect(['ticket/view', 'id' => $ticket_id, '#' => 'tab_logs']);
    }

    /**
     * Displays a Log model.
     * @param string $type log file type
     * @param string $token ticket token
     * @param string $date date associated to the log file
     */
    public function actionView($type, $token, $date)
    {
        $model = $this->findModel([
            'type' => $type,
            'token' => $token,
            'date' => $date,
        ]);

        return $this->renderAjax('/log/view', [
            'model' => $model,
        ]);
    }

    /**
     * Downloads a Log model.
     * @param string $type log file type
     * @param string $token ticket token
     * @param string $date date associated to the log file
     */
    public function actionDownload($type, $token, $date)
    {
        $model = $this->findModel([
            'type' => $type,
            'token' => $token,
            'date' => $date,
        ]);

        return Yii::$app->response->sendContentAsFile(implode('', $model->contents), basename($model->path), [
            'inline' => false,
        ]);
    }

    /**
     * Finds the Log model.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param array $params
     * @return Log the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($params)
    {
        if (($model = Log::findOne($params)) !== null) {
            if (($ticket = Ticket::findOne(['token' => $model->token])) !== null) {
                return $model;
            }
        }
        throw new NotFoundHttpException(\Yii::t('app', 'The requested page does not exist.'));
    }

}
