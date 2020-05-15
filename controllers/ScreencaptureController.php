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


            if ($file == "subtitles.webvtt") {
                return Yii::$app->response->sendContentAsFile("WEBVTT

1
00:00:01.500 --> 00:00:03.500
When the moon <00:00:02.500>hits your eye

2
00:00:03.500 --> 00:00:05.500
Like a <00:00:04.000>big-a <00:00:04.500>pizza <00:00:05.000>pie

3
00:00:05.500 --> 00:00:06.500
That's <00:00:06.000>amore

4
00:00:07.000 --> 00:00:55.500
Long living <c.class><v delay-500>text</v></c>

", "subtitles.vtt", [
                    'mimeType' => "text/vtt",
                    'inline' => false,
                ]);
            }

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

}
