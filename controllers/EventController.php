<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\models\EventItem;
use app\models\EventStream;
use app\models\Ticket;

/**
 * EventController implements the stream actions for Event model.
 */
class EventController extends Controller
{

    /**
     * Streams all Event models to the client browser.
     * @return mixed
     */
    public function actionStream($uuid)
    {
        $stream = $this->findModel($uuid);
        return $this->setupStream($stream);
    }


    /**
     * Streams all Event models for the client agent.
     * @return mixed
     */
    public function actionAgent($uuid, $token)
    {
        $ticket = $this->findTicket($token);
        if (($stream = EventStream::findOne(['uuid' => $uuid])) === null) {
            $stream = new EventStream(['uuid' => $uuid]);
            $stream->listenEvents = implode(',', [
                'agent/' . $token,
                'meta'
            ]);
            $stream->save();
        }
        return $this->setupStream($stream);
    }


    /**
     * Sets up the stream with its start/stop and resume events with the listener
     * for new live events
     *
     * @return mixed
     */
    public function setupStream($stream)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        $uuid = $stream->uuid;
        $stream->timeLimit = YII_ENV_DEV ? 30 : 300;

        $stream->on(EventStream::EVENT_STREAM_STARTED, function() use ($uuid, $stream) {
            $event = new EventItem([
                'event' => 'meta',
                'data' => json_encode([
                    'state' => 'event stream started',
                    'timeLimit' => $stream->timeLimit,
                ])
            ]);
            $this->sendMessage($this->renderPartial('/event/message', [
                'model' => $event,
            ]), $uuid);
        });

        $stream->on(EventStream::EVENT_STREAM_STOPPED, function() use ($uuid) {
            $event = new EventItem([
                'event' => 'meta',
                'data' => json_encode([
                    'state' => 'event stream finished'
                ]),
                'retry' => 1000
            ]);
            $this->sendMessage($this->renderPartial('/event/message', [
                'model' => $event,
            ]), $uuid);
        });

        $stream->on(EventStream::EVENT_STREAM_RESUMED, function() use ($uuid, $stream) {
            $event = new EventItem([
                'event' => 'meta',
                'data' => json_encode([
                    'state' => 'event stream resumed',
                    'timeLimit' => $stream->timeLimit,
                ])
            ]);
            $this->sendMessage($this->renderPartial('/event/message', [
                'model' => $event,
            ]), $uuid);
        });

        ob_end_flush();
        ob_start();
        $stream->start();

        while ($stream->onEvent() === true) {
            $message = '';
            foreach($stream->events as $model){

                if (!in_array($model->id, $stream->sentIds)) {

                    // translate all values in data set in translate_data if a translation category is set
                    if ($model->category != null) {
                        $data = json_decode($model->data, true);
                        $translate_data = json_decode($model->translate_data, true);
                        foreach ($data as $key => $value) {
                            $params = isset($translate_data[$key])
                                ? $translate_data[$key]
                                : null;
                            $data[$key] = \Yii::t($model->category, $value, $params);
                        }
                        $model->data = json_encode($data);
                    }

                    $message .= $this->renderPartial('/event/message', [
                        'model' => $model,
                    ]);

                    // add the id of the event to the sentIds array
                    array_push($stream->sentIds, $model->id);
                }
            }

            if(!empty($message)){
                $this->sendMessage($message, $uuid);
            }
        }

        $stream->stop();
        exit(); # or return ob_get_clean();
    }

    public function sendMessage($message, $uuid)
    {
        echo $message;
        ob_flush();
        flush();
        if (YII_ENV_DEV) {
            file_put_contents(\Yii::$app->params['daemonLogFilePath'] . '/debug-stream-uuid=' . $uuid, $message, FILE_APPEND);
        }
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
        throw new NotFoundHttpException(\Yii::t('app', 'The requested page does not exist.'));
    }

    /**
     * Finds the Ticket model based on token.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $token
     * @return EventStream the loaded model
     * @throws NotFoundHttpException if the model cannot be found.
     */
    protected function findTicket($token)
    {
        if (($model = Ticket::findOne(['token' => $token])) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(\Yii::t('app', 'The requested page does not exist.'));
    }

}
