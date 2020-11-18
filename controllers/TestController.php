<?php

namespace app\controllers;

use Yii;
use app\models\forms\EventItemSend;
use app\models\forms\EventStreamListen;
use app\components\AccessRule;

/**
 * TestController
 */
class TestController extends BaseController
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
                        'allow' => YII_ENV_DEV,
                    ],
                ],
            ],
        ];
    }

    /**
     * Send event test page.
     * @return mixed
     */
    public function actionSend()
    {
        $event = new EventItemSend();
        if ($event->load(Yii::$app->request->post())) {
            $event->generateEvents();
        }
        $event->isNewRecord = !isset($event->event);

        return $this->render('event/send', [
            'event' => $event,
        ]);
    }

    /**
     * Listen to events test page.
     * @return mixed
     */
    public function actionListen()
    {
        $stream = new EventStreamListen();
        $stream->load(Yii::$app->request->post()) && $stream->validate();
        $stream->isNewRecord = !isset($stream->listenEvents);

        return $this->render('event/listen', [
            'stream' => $stream,
        ]);
    }

}
