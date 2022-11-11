<?php

namespace app\controllers;

use Yii;
use app\models\forms\EventItemSend;
use app\models\forms\AgentEventSend;
use app\models\forms\EventStreamListen;
use yii\web\NotFoundHttpException;

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
     * Send agent event test page.
     * @return mixed
     */
    public function actionAgent()
    {
        $event = new AgentEventSend();
        if ($event->load(Yii::$app->request->post())) {
            if ($event->generateEvents() === false) {
                throw new NotFoundHttpException(Yii::t('app', 'The ticket does not exist.'));
            }
        }
        $event->isNewRecord = !isset($event->event);

        return $this->render('event/agent', [
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
