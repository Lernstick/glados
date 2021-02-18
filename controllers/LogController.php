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
     * Lists all Log models.
     * @return mixed
     */
    public function actionIndex($ticket_id)
    {
        return $this->redirect(['ticket/view', 'id' => $ticket_id, '#' => 'tab_logs']);
    }

    /**
     * Displays Log model.
     * @param array $params params
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
     * Displays Log model.
     * @param array $params params
     */
    public function actionDownload($type, $token, $date)
    {
        $model = $this->findModel([
            'type' => $type,
            'token' => $token,
            'date' => $date,
        ]);

        return Yii::$app->response->sendContentAsFile(implode('', $model->contents), basename($model->path), [
            //'mimeType' => $model->getMimeType($model->path),
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
                if ($this->checkRbac($ticket->exam->user_id)) {
                    return $model;
                }
            }
        }
        throw new NotFoundHttpException(\Yii::t('app', 'The requested page does not exist.'));
    }

}
