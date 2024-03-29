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
     * @const string Javascript code to execute when registering a new active event.
     */
    const JS_REGISTER_SCRIPT = "
        if (!!window.EventSource && event) {
            var listener = function(e) {
                var jsonSelector = JSON.parse('{enc_jsonSelector}');
                data = JSON.parse(e.data);
                if (typeof data.data != 'string' && jsonSelector in data.data) {
                    var value = data.data[jsonSelector];
                } else {
                    var value = data.data;
                }
                if (value.status == 'stopped') {
                    debugHandler(e, listener);
                    {onStop}
                } else if(value.status == 'started') {
                    debugHandler(e, listener);
                    {onStart}
                } else if(jsonSelector == '' || jsonSelector == '*' || jsonSelector in data.data) {
                    debugHandler(e, listener);
                    {jsHandler}
                }
            };

            var name = '{name}';
            var group = '{group}';
            var jsonSelector = '{jsonSelector}';

            if (typeof eventListeners[name] == 'undefined') {
                eventListeners[name] = {};
            }
            if (typeof eventListeners[name][group] == 'undefined') {
                eventListeners[name][group] = {};
            }
            var hash = String(listener).hashCode();
            if (typeof eventListeners[name][group][hash] == 'undefined') {
                event.source.addEventListener(name, listener);
                if (YII_DEBUG) {
                    console.log('Listener added for event:', name, ', selector:', jsonSelector, ', listener:', listener);
                }
                eventListeners[name][group][hash] = {
                    listener: listener,
                    jsonSelector: '{jsonSelector}',
                };
            }
        }
    ";

    /**
     * @const string Javascript code to execute when de-registering a new active event.
     */
    const JS_DEREGISTER_SCRIPT = "
        if (!!window.EventSource && event) {
            var name = '{name}';
            var group = '{group}';
            if (typeof eventListeners[name] != 'undefined') {
                for (var hash in eventListeners[name][group]) {
                    var listener = eventListeners[name][group][hash].listener;
                    var jsonSelector = eventListeners[name][group][hash].jsonSelector;
                    event.source.removeEventListener(name, listener);
                    if (YII_DEBUG) {
                        console.log('Listener removed for event:', name, ', selector:', jsonSelector, ', listener:', listener);
                    }
                }
            } else {
                eventListeners[name] = {};
            }
            eventListeners[name][group] = {};
        }";

    /**
     * @const string Javascript code when no jsHandler is given.
     */
    const JS_DEFAULT_JSHANDLER = 'function(d, s){
        if(typeof s.innerHTML != "undefined" && typeof d != "undefined"){
            s.innerHTML = d;
        }
        if(s.getAttribute("change-animation")){
            s.style.animation = "";
            setTimeout(function (){s.style.animation = s.getAttribute("change-animation");},10);

        }
    }';

    /**
     * @const string Javascript code to register a new event stream.
     */
    const JS_REGISTER_EVENTSTREAM = "
        uuid = '{uuid}';
        event = new EventStream('{url}');
        eventListeners = {};
    ";

    /**
     * @var string the content between the opening and closing tag.
     */
    public $content;

    /**
     * @var string the name of the event on which the js handler should listen on. Can also be 
     * prepended with a group name. This group name is later used to determine which events should
     * be replaced if the request is ajax. Events that are in the same group identifier, will be
     * replaced with the new events in case of an ajax request.
     * Examples:
     *  - "ticket/1234"         // no group name
     *  - "monitor:ticket/1234" // sets the group name to "monitor"
     */
    public $event;

    /**
     * @var string the name of the property in the json enocded data of the event. If not set the 
     * whole data array is passed to the handler function. In that case, the function is exectued 
     * every time the event raises, not only if the selector exists in it. The function then has to
     * process the data itself.
     */
    public $jsonSelector;

    /**
     * @var string the definition of an anonymous javascript function which handles the event. The
     * function must have the following footprint:
     * ```javascript
     * function (data, selector) {
     *    // handlelogic
     * }
     * ```
     * data: is the data from the event, if [[jsonSelector]] is set, only the part decending to it will be passed
     * selector: the DOM object of the HTML document referring to the ActiveEventField itself.
     */
    public $jsHandler;

    /**
     * @var string|array TODO.
     */
    public $jsFormatter = 'raw';

    public $register;

    #TODO
    public $onStart;
    public $onStop;

    /**
     * Render the function call to Javascript
     *
     * @param string function the Javascript function to call, it will be called by:
     * ```javascript
     * ({function}) ({value}, $('#{id}')[0]);
     * ```
     * where {value} is the formatter data according to [[jsFormatter]].
     *
     * @see [[jsHandler]]
     * @return null|string
     */
    private function call($function) {
        $value = 'value';
        if (is_string($this->jsFormatter)) {
            $value = substitute('formatter_as{jsFormatter}(value)', [
                'jsFormatter' => ucfirst($this->jsFormatter),
            ]);
        } elseif (is_array($this->jsFormatter)) {
            $args = json_encode(array_slice($this->jsFormatter, 1)[0]);
            $value = substitute('formatter_as{jsFormatter}(value, {args})', [
                'jsFormatter' => ucfirst($this->jsFormatter[0]),
                'args' => $args,
            ]);
        } else {
            trigger_error("jsFormatter is neither string nor array.");
        }
        if (!empty($function)) {
            //var_dump($value);
            return substitute("({function})({value}, $('#{id}')[0]);", [
                'function' => $function,
                'id' => $this->id,
                'value' => $value,
            ]);
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (empty($this->jsHandler)) {
            $this->jsHandler = self::JS_DEFAULT_JSHANDLER;
        }

        $name = $this->getRawEvent($this->event);
        $group = $this->getGroup($this->event);

        $this->register = substitute(self::JS_REGISTER_SCRIPT, [
            'enc_jsonSelector' => json_encode($this->jsonSelector),
            'onStop' => $this->call($this->onStop),
            'onStart' => $this->call($this->onStart),
            'jsHandler' => $this->call($this->jsHandler),
            'name' => $name,
            'group' => $group,
            'jsonSelector' => $this->jsonSelector,
        ]);

        $this->view->params['listenEvents'][] = [
            'name' => $this->event,
            'register' => $this->register,
        ];

        if (!isset($this->view->params['uuid'])) {
            $this->view->params['uuid'] = \Yii::$app->request->isAjax ? \Yii::$app->request->get('_') : generate_uuid();
        }

        foreach (ActiveEventField::$stack as $widget) {
            $widget->clientOptions = ['data' => ['_' => $this->view->params['uuid']]];
        }

        // the registerAllEvents event should always be refreshed
        \Yii::$app->view->off(\yii\web\View::EVENT_END_PAGE, [__CLASS__, 'registerAllEvents']);
        \Yii::$app->view->on(
            \yii\web\View::EVENT_END_PAGE,
            [__CLASS__, 'registerAllEvents'],
            $this
        );

    }

    /**
     * Returns the group name, when given an event name
     * Example:
     *  - $event="monitor:/ticket/1234", it returns "monitor"
     *  - $event="ticket/1234", it returns null
     * @return string|null the group name or null if no group name is present
     */
    static public function getGroup($event)
    {
        $parts = explode(':', $event, 2);
        if (array_key_exists(1, $parts)) {
            return $parts[0];
        }
        return null;
    }

    /**
     * Returns the raw event name, when given an event
     * Example:
     *  - $event="monitor:/ticket/1234", it returns "ticket/1234"
     *  - $event="ticket/1234", it returns "ticket/1234"
     * @return string the raw event name
     */
    static public function getRawEvent($event)
    {
        return preg_replace('/^.+\:/', '', $event);
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

    /**
     * Registers all events that are present in some AcitveEventField
     * @return void
     */
    public function registerAllEvents($yiiEvent)
    {
        // get all events currently registered in views as well as the uuid
        $uuid = $yiiEvent->data->view->params['uuid'];
        $currentEvents = $yiiEvent->data->view->params['listenEvents'];
        $script = '';

        if ($uuid == null) {
            return;
        }

        if (\Yii::$app->params['liveEvents'] && extension_loaded('inotify') && isset($currentEvents) && isset($uuid)) {

            // get the stream
            if (($stream = EventStream::findOne(['uuid' => $uuid])) !== null) {
                $dbEvents = explode(',', $stream->listenEvents);
            } else {
                $stream = new EventStream(['uuid' => $uuid]);
                $yiiEvent->data->view->registerJs(substitute(self::JS_REGISTER_EVENTSTREAM, [
                    'uuid' => $uuid,
                    'url' => Url::to(['/event/stream', 'uuid' => $uuid]),
                ]));
                $dbEvents = [];
            }

            // get all event group names from the events currently registered in views
            $groups = [];
            foreach ($currentEvents as $event) {
                $group = self::getGroup($event['name']);
                if ($group !== null) {
                    $groups[] = $group;
                }
            }
            $groups = array_unique($groups);

            // remove all events that are in a group of a current event and keep all 
            // other events in the stream
            $setEvents = array_filter($dbEvents, function($val) use ($groups) {
                $myGroup = self::getGroup($val);
                if ($myGroup !== null) {
                    return !in_array($myGroup, $groups);
                } else {
                    return true;
                }
            });

            // remove all listeners of events that are in the same group of a currently registered event
            // dbEvents \ setEvents, elements in dbEvents but not in setEvents
            $removeEvents = array_diff($dbEvents, $setEvents);
            foreach ($removeEvents as $event) {
                $name = self::getRawEvent($event);
                $group = self::getGroup($event);
                $script .= substitute(self::JS_DEREGISTER_SCRIPT, [
                    'name' => $name,
                    'group' => $group,
                ]);
            }

            // add current events to the array of listen events
            foreach ($currentEvents as $event) {
                $setEvents[] = $event['name'];
                $script .= $event['register'];
            }
            $setEvents = array_unique($setEvents);

            // save to database
            $stream->listenEvents = implode(',', $setEvents);
            $stream->save();
            $yiiEvent->data->view->registerJs($script);

            // in case of an ajax request
            if (\Yii::$app->request->isAjax) {

                // Trigger the bump event for any listening event stream.
                // They will eventually reload their ActiveRecord.
                $eventItem = new EventItem([
                    'event' => 'event/' . $uuid,
                    'data' => 'bump',
                ]);

                $eventItem->touchFile(substitute('{tmpPath}/inotify/event/{uuid}', [
                    'tmpPath' => \Yii::$app->params['tmpPath'],
                    'uuid' => $uuid,
                ]), 'bump');
            }
        }
    }
}