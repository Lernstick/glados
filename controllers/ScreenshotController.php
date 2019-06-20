<?php

namespace app\controllers;

use Yii;
use app\models\Screenshot;
use app\models\ScreenshotSearch;
use app\models\Ticket;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\components\AccessRule;

/**
 * ScreenshotController implements the CRUD actions for Screenshot model.
 */
class ScreenshotController extends Controller
{

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
     * Displays a single Screenshot model.
     * @param string $token
     * @param integer $date
     * @param string $type this can be normal or thumb
     * @return mixed
     */
    public function actionView($token, $date, $type = 'normal')
    {
        if (($model = Screenshot::findOne($token, $date)) !== null){
            if ($type = 'normal') {
                return \Yii::$app->response->sendFile($model->path, null, ['inline' => true]);
            } else if ($type = 'thumb') {
                return \Yii::$app->response->sendFile($model->thumbnail, null, ['inline' => true]);
            } else {
                throw new NotFoundHttpException(\Yii::t('app', 'The requested page does not exist.'));
            }
        } else {
            throw new NotFoundHttpException(\Yii::t('ticket', 'The screenshot does not exist.'));
        }
    }

    /**
     * Displays a single Screenshot thumbnail.
     * @param string $token
     * @param integer $date
     * @return mixed
     */
    /*public function actionThumbnail($token, $date)
    {
        if (($model = Screenshot::findOne($token, $date)) !== null){
            return \Yii::$app->response->sendFile($model->thumbnail, null, ['inline' => true]);
        }else{
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }*/

    /**
     * Displays a single Screenshot model.
     * @param string $token
     * @return mixed
     */
    public function actionSnap($token)
    {
        $model = new Screenshot();
        $ticket = Ticket::findOne(['token' => $token]);
        $cmd = "DISPLAY=:0 sudo -u user import -silent -window root - | " .
            "convert - " .
            "-filter Triangle " .
            "-define filter:support=2 " .
            "-unsharp 0.25x0.08+8.3+0.045 " .
            "-dither None " .
            "-posterize 136 " .
            "-quality 62 " .
            "-define jpeg:fancy-upsampling=off " .
            "-define png:compression-filter=5 " .
            "-define png:compression-level=9 " .
            "-define png:compression-strategy=1 " .
            "-define png:exclude-chunk=all " .
            "-interlace none " .
            "-colorspace sRGB " .
            "jpeg:-";
        $img = $ticket->runCommand($cmd, "C", 10);
        if ($img[1] != 0) {
            throw new NotFoundHttpException(\Yii::t('ticket', 'The screenshot could not be generated.'));
        } else {
            return \Yii::$app->response->sendContentAsFile($img[0], $token . '.jpg', [
                'mimeType' => 'image/jpeg',
                'inline' => true
            ]);
        }
    }

}
