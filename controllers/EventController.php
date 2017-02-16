<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\models\EventItem;
use app\models\EventStream;

/**
 * EventController implements the stream actions for Event model.
 */
class EventController extends Controller
{

    /**
     * Streams all Event models.
     * @return mixed
     */
    public function actionStream($uuid)
    {
    
        $stream = $this->findModel($uuid);

        $stream->timeLimit = YII_ENV_DEV ? 60 : 300;

        //$user_id = \Yii::$app->user->id;
        //$user_id = 1;
        $pathPrefixes = array_merge(
            []
            //[ 'user/' . $user_id ],
            //array_keys(\Yii::$app->authManager->getRolesByUser($user_id))
        );
        $stream->pathPrefixes = $pathPrefixes;

        $stream->on(EventStream::EVENT_STREAM_STARTED, function() {
            $event = new EventItem(['event' => 'meta', 'data' => json_encode(['state' => 'event stream started'])]);
            $this->sendMessage($this->renderPartial('/event/message', [
                'model' => $event,
            ]));
        });

        $stream->on(EventStream::EVENT_STREAM_STOPPED, function() {
            $event = new EventItem(['event' => 'meta', 'data' => json_encode(['state' => 'event stream finished']), 'retry' => 1000]);
            $this->sendMessage($this->renderPartial('/event/message', [
                'model' => $event,
            ]));
        });

        $stream->on(EventStream::EVENT_STREAM_RESUMED, function() {
            $event = new EventItem(['event' => 'meta', 'data' => json_encode(['state' => 'event stream resumed'])]);
            $this->sendMessage($this->renderPartial('/event/message', [
                'model' => $event,
            ]));
        });

        $stream->start();

        while($stream->onEvent() === true){
            $message = '';
            foreach($stream->events as $model){
                $message .= $this->renderPartial('/event/message', [
                    'model' => $model,
                ]);
            }

            if(!empty($message)){
                $this->sendMessage($message);
            }
        }

        $stream->stop();

    }


    public function sendMessage($message)
    {
        echo $message;
        ob_flush();
        flush();
    }

    /**
     * Finds the EventStream model based on its uuid value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $uuid
     * @return EventStream the loaded model
     * @throws NotFoundHttpException if the model cannot be found.
     */
    protected function findModel($uuid)
    {
        if (($model = EventStream::findOne(['uuid' => $uuid])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }

}
