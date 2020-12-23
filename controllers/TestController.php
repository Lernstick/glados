<?php

namespace app\controllers;

use Yii;
use app\models\forms\EventItemSend;
use app\models\forms\AgentEventSend;
use app\models\forms\EventStreamListen;
use app\models\forms\ElasticsearchQuery;
use app\components\AccessRule;
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

    /**
     * Run an Elasticsearch query.
     * @return mixed
     */
    public function actionQuery()
    {
        $model = new ElasticsearchQuery();
        $response = null;
        $host = 'localhost:9200';
        try {
            $es = \Yii::$app->get('elasticsearch');
            $online = true;
        } catch (\Exception $e) {
            $online = false;
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $raw = !empty($model->data);
            $method = strtolower($model->method);

            try {
                $response = $model->getDb()->{$method}($model->url, [], $model->data, $raw);
                if (property_exists($es, 'nodes') && array_key_exists(0, $es->nodes) && array_key_exists('http_address', $es->nodes[0])) {
                    $host = \Yii::$app->get('elasticsearch')->nodes[0]['http_address'];
                }
                $online = true;
            } catch (\Exception $e) {
                \Yii::warning($e->getMessage(), __CLASS__);
                $response = $e->getMessage();
                $online = false;
            }

            $model->isNewRecord = false;
        }

        return $this->render('elasticsearch/query', [
            'model' => $model,
            'host' => $host,
            'online' => $online,
            'response' => $response,
        ]);
    }

}
