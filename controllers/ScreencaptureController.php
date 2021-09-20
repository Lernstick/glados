<?php

namespace app\controllers;

use Yii;
use app\models\Screencapture;
use app\models\Ticket;
use yii\web\NotFoundHttpException;

/**
 * ScreencaptureController implements the CRUD actions for Screencapture model.
 */
class ScreencaptureController extends BaseController
{

    /**
     * @inheritdoc
     */
    public $owner_actions = ['view', 'log', 'keylogger', 'download'];

    /**
     * @{inheritdoc}
     */
    public function route_mapping ()
    {
        return ['*' => 'ticket/view'];
    }

    /**
     * @inheritdoc
     */
    public function getOwner_id()
    {
        $id = Yii::$app->request->get('ticket_id');
        if (($model = Ticket::findOne($id)) !== null) {
            return $model->exam->user_id;
        } else {
            return null;
        }
    }

    /**
     * Displays a single Screencapture model.
     * @param string $ticket_id id of the Ticket model.
     * @return mixed
     */
    public function actionView($ticket_id, $file)
    {
        $model = $this->findModel($ticket_id);
        if ( ($contents = $model->getFile($file)) !== null ) {
            return Yii::$app->response->sendContentAsFile($model->getFile($file), basename($file), [
                'mimeType' => $model->getMimeType($file),
                'inline' => false,
            ]);
        } else {
            throw new NotFoundHttpException($file . ': No such file or directory.');
        }
    }

    /**
     * Displays the ffmpeg log by Screencapture model.
     * @param integer $ticket_id id of the Ticket model.
     * @return The response object
     */
    public function actionLog($ticket_id)
    {
        $model = $this->findModel($ticket_id);
        return $this->renderAjax('/screen_capture/log', [
            'log' => $model->screencaptureLog,
        ]);
    }

    /**
     * Displays the keylogger log by Screencapture model.
     * @param integer $ticket_id id of the Ticket model.
     * @return The response object
     */
    public function actionKeylogger($ticket_id)
    {
        $model = $this->findModel($ticket_id);
        return $this->renderAjax('/screen_capture/keylogger', [
            'log' => $model->getKeyloggerLog(true),
        ]);
    }

    /**
     * Displays the keylogger log by Screencapture model.
     * @param integer $ticket_id id of the Ticket model.
     * @return The response object
     */
    public function actionDownload($ticket_id)
    {
        $model = $this->findModel($ticket_id);
        return \Yii::$app->response->sendContentAsFile($model->getKeyloggerLog(false), 'keylogger.txt', [
            'mimeType' => 'application/octet-stream',
            'inline' => true
        ]);
    }

    /**
     * Finds the ScreenCapture model based on the ticket id.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param integer $id ticket id
     * @return Screencapture the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Screencapture::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(\Yii::t('app', 'No screen capture found for this ticket.'));
    }

}
