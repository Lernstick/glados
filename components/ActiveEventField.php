<?php

namespace app\components;

use yii\widgets\Pjax;
use yii\helpers\Html;
use app\assets\EventAsset;
use app\models\EventItem;
use app\models\EventStream;
use yii\helpers\Url;

class ActiveEventField extends Pjax
{

     /**
     * @var string the content between the opening and closing tag.
     */
    public $content;

     /**
     * @var string the name of the event on which the js handler should listen on
     */
    public $event;

     /**
     * @var string the name of the property in the json enocded data of the event. If not set the whole data array is
     * passed to the handler function. In that case, the function is exectued every time the event raises, not only if the
     * selector exists in it. The function then has to process the data itself.
     */
    public $jsonSelector;

     /**
     * @var string the definition of an anonymous javascript function which handles the event. That function must have the 
     * following syntax:
     * ```javascript
     * function(data, selector){handlelogic}
     * ```
     * data: is the data from the event, if [[jsonSelector]] is set, only the part decending to it will be passed
     * selector: the DOM object of the HTML document refering to the ActiveEventField itself.
     */
    public $jsHandler;

     /**
     * @var string marker
     */
    public $marker;

    public $register;

    #TODO
    public $onStart;
    public $onStop;


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if(empty($this->jsHandler)){
            $this->jsHandler = 'function(d, s){
                if(typeof s.innerHTML != "undefined" && typeof d != "undefined"){
                    s.innerHTML = d;
                }
                if(s.getAttribute("change-animation")){
                    s.style.animation = "";
                    setTimeout(function (){s.style.animation = s.getAttribute("change-animation");},10);

                }
            }';
        }

        $this->register = "
            if (!!window.EventSource && event) {
                var listener = function(e) {
                    var jsonSelector = JSON.parse('" . json_encode($this->jsonSelector) . "');
                    data = JSON.parse(e.data);
                    if(jsonSelector in data.data){
                        var value = data.data[jsonSelector];
                    }else{
                        var value = data.data;
                    }
                    if(value.status == 'stopped'){
                        debugHandler(e, listener);
                        " . ( empty($this->onStop) ? null : "(" . $this->onStop . ")(value, $('#" . $this->id . "')[0]);" ) . "
                    }else if(value.status == 'started'){
                        debugHandler(e, listener);
                        " . ( empty($this->onStart) ? null : "(" . $this->onStart . ")(value, $('#" . $this->id . "')[0]);" ) . "
                    }else if(jsonSelector == '' || jsonSelector in data.data){
                        debugHandler(e, listener);
                        " . ( empty($this->jsHandler) ? null : "(" . $this->jsHandler . ")(value, $('#" . $this->id . "')[0]);" ) . "
                    }
                };

                var string = listener + '" . $this->event . "';
                if (typeof eventListeners[string] == 'undefined') {
                    event.source.addEventListener('" . $this->event . "', listener);
                    if (YII_DEBUG) {
                        console.log('Listener added for event " . ((empty($this->jsonSelector) || $this->event == $this->jsonSelector) ? $this->event : $this->event . '/' . $this->jsonSelector) . ", listener: ', listener);
                    }
                    eventListeners[string] = true;
                }

            }
        ";
        $this->view->params['listenEvents'][] = [$this->event, $this->register, $this->marker];

        if (!isset($this->view->params['uuid'])) {
            $this->view->params['uuid'] = \Yii::$app->request->isAjax ? \Yii::$app->request->get('_') : generate_uuid();
        }

        foreach (ActiveEventField::$stack as $widget) {
            $widget->clientOptions = ['data' => ['_' => $this->view->params['uuid']]];
        }

        \Yii::$app->view->off(\yii\web\View::EVENT_END_PAGE, [__CLASS__, 'handleSend']);
        \Yii::$app->view->on(
            \yii\web\View::EVENT_END_PAGE,
            [__CLASS__, 'handleSend'],
            $this
        );

    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        EventAsset::register($this->view);
        echo $this->content;

        return parent::run();
    }

    public function handleSend($yiiEvent)
    {
        $uuid = $yiiEvent->data->view->params['uuid'];
        $listenEvents = $yiiEvent->data->view->params['listenEvents'];
        $script = '';
        $e = [];


        if ($uuid == null) {
            return;
        }

        if (\Yii::$app->request->isAjax) {
            if (($stream = EventStream::findOne(['uuid' => $uuid])) !== null) {
                $e = explode(',', $stream->listenEvents);
            } else {
                $stream = new EventStream(['uuid' => $uuid]);
                $yiiEvent->data->view->registerJs("uuid = '" . $uuid . "';" . PHP_EOL .
                    "event = new EventStream('" . 
                    Url::to([
                        '/event/stream',
                        'uuid' => $uuid,
                    ]) . "');" . PHP_EOL . 
                    "eventListeners = [];" . PHP_EOL
                );
            }

            foreach ($listenEvents as $event) {
                if ($event[2] !== null) {
                    $e  = preg_grep('/:' . $event[2] . '$/', $e, PREG_GREP_INVERT);
                }
            }

            foreach ($listenEvents as $event) {
                $e[] = $event[2] !== null ? $event[0] . ':' . $event[2] : $event[0];
                $script .= $event[1];
            }
            $e = array_unique($e);

            $stream->listenEvents = implode(',', $e);
            $stream->save();
            $yiiEvent->data->view->registerJs($script);

            $eventItem = new EventItem([
                'event' => 'event/' . $uuid,
                'data' => 'bump',
            ]);
            $eventItem->touchFile('/tmp/user/event/' . $uuid, 'bump');


        } else {

            if (\Yii::$app->params['liveEvents'] && extension_loaded('inotify') && isset($listenEvents) && isset($uuid)) {

                foreach ($listenEvents as $event) {
                    $e[] = $event[2] !== null ? $event[0] . ':' . $event[2] : $event[0];
                    $script .= $event[1];
                }
                $e = implode(',', array_unique($e));

                $stream = new EventStream([
                    'uuid' => $uuid,
                    'listenEvents' => $e,
                ]);
                $stream->save();

                $yiiEvent->data->view->registerJs("uuid = '" . $uuid . "';" . PHP_EOL .
                    "event = new EventStream('" . 
                    Url::to([
                        '/event/stream',
                        'uuid' => $uuid,
                    ]) . "');" . PHP_EOL . 
                    "eventListeners = [];" . PHP_EOL
                );

                $yiiEvent->data->view->registerJs($script);

            }

        }
    }

}
