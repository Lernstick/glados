<?php

namespace app\controllers;

use Yii;
use app\models\Screencapture;
use app\models\Ticket;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\components\AccessRule;

/**
 * ScreencaptureController implements the CRUD actions for Screencapture model.
 */
class ScreencaptureController extends Controller
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
     * Displays a single Screencapture model.
     * @param string $id
     * @return mixed
     */
    public function actionView($id, $file)
    {
        $ticket = Ticket::findOne($id);
        if (($model = $ticket->screencapture) !== null) {

            if ( ($contents = $model->getFile($file)) !== null ) {
                return Yii::$app->response->sendContentAsFile($model->getFile($file), basename($file), [
                    'mimeType' => $model->getMimeType($file),
                    'inline' => false,
                ]);
            } else {
                throw new NotFoundHttpException($file . ': No such file or directory.');
            }

        } else {
            throw new NotFoundHttpException(\Yii::t('ticket', 'The screen capture does not exist.'));
        }
    }

    /**
     * Displays the ffmpeg log by Screencapture model.
     * @param integer $ticket_id id of the Ticket model.
     * @return The response object
     */
    public function actionLog($ticket_id)
    {
        if (($screencapture = Screencapture::findOne($ticket_id)) !== null) {
            return $this->renderAjax('/screen_capture/log', [
                'log' => $screencapture->screencaptureLog,
            ]);
        } else {
            throw new NotFoundHttpException(\Yii::t('ticket', 'The screen capture log file does not exist.'));
        }
    }

    /**
     * Displays the keylogger log by Screencapture model.
     * @param integer $ticket_id id of the Ticket model.
     * @return The response object
     */
    public function actionKeylogger($ticket_id)
    {
        if (($screencapture = Screencapture::findOne($ticket_id)) !== null) {
            return $this->renderAjax('/screen_capture/keylogger', [
                'log' => $screencapture->getKeyloggerLog(true),
            ]);
        } else {
            throw new NotFoundHttpException(\Yii::t('ticket', 'The keylogger log file does not exist.'));
        }
    }

    /**
     * Displays the keylogger log by Screencapture model.
     * @param integer $ticket_id id of the Ticket model.
     * @return The response object
     */
    public function actionDownload($ticket_id)
    {
        if (($screencapture = Screencapture::findOne($ticket_id)) !== null) {
            return \Yii::$app->response->sendContentAsFile($screencapture->getKeyloggerLog(false), 'keylogger.txt', [
                'mimeType' => 'application/octet-stream',
                'inline' => true
            ]);
        } else {
            throw new NotFoundHttpException(\Yii::t('ticket', 'The keylogger log file does not exist.'));
        }
    }

}
