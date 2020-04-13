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

            $path = $file == 'master.m3u8' ? $model->master : $model->segment($file);

            if (!file_exists($path)) {
                throw new NotFoundHttpException($file . ': No such file or directory.');
            }

            $ext = pathinfo($path, PATHINFO_EXTENSION);
            $arr = [
                'm3u8' => 'application/x-mpegURL',
                'ts' => 'video/MP2T',
            ];
            $mimeType = array_key_exists($ext, $arr) ? $arr[$ext] : 'application/octet-stream';

            if ($ext == 'm3u8') {
                $contents = file_get_contents($path);
                if ($ticket->state == Ticket::STATE_CLOSED || $ticket->state == Ticket::STATE_SUBMITTED) {
                    // simulate a vod stream
                    $contents = str_replace("#EXT-X-PLAYLIST-TYPE:EVENT", "#EXT-X-PLAYLIST-TYPE:VOD", $contents) . "#EXT-X-ENDLIST" . PHP_EOL;
                } else {
                    // simulate a live stream
                    $contents = str_replace("#EXT-X-ENDLIST", "", $contents);
                }
                return Yii::$app->response->sendContentAsFile($contents, basename($path), [
                    'mimeType' => $mimeType,
                    'inline' => false,
                ]);
            } else {
                return Yii::$app->response->sendFile($path, basename($path), [
                    'mimeType' => $mimeType,
                    'inline' => false,
                ]);
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
