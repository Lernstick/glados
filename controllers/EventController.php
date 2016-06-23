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
    public function actionStream($uuid = null, $listenEvents = null, $listenFifos = null)
    {
    
        if(($stream = EventStream::findOne($uuid)) === null){
            $stream = new EventStream([
                'uuid' => $uuid !== null ? $uuid : generate_uuid(),
            ]);
        }
        $stream->timeLimit = 600;
        isset($listenEvents) ? $stream->listenEvents = explode(',', $listenEvents) : null;
        isset($listenFifos) ? $stream->listenFifos = explode(',', $listenFifos) : null;

        //$user_id = \Yii::$app->user->id;
        //$user_id = 1;
        $pathPrefixes = array_merge(
            []
            //[ 'user/' . $user_id ],
            //array_keys(\Yii::$app->authManager->getRolesByUser($user_id))
        );
        $stream->pathPrefixes = $pathPrefixes;


        if(YII_ENV_DEV){
            $stream->on(EventStream::EVENT_STREAM_STARTED, function() {
                $event = new EventItem(['data' => 'event stream started']);
                $this->sendMessage($this->renderPartial('/event/message', [
                    'model' => $event,
                ]));
            });

            $stream->on(EventStream::EVENT_STREAM_STOPPED, function() {
                $event = new EventItem(['data' => 'event stream finished', 'retry' => 1000]);
                $this->sendMessage($this->renderPartial('/event/message', [
                    'model' => $event,
                ]));
            });

            $stream->on(EventStream::EVENT_STREAM_RESUMED, function() {
                $event = new EventItem(['data' => 'event stream resumed']);
                $this->sendMessage($this->renderPartial('/event/message', [
                    'model' => $event,
                ]));
            });
        }

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

}
