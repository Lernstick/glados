<?php

namespace app\components;

use yii\widgets\Pjax;
use yii\helpers\Html;
use app\assets\EventAsset;

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

        $register = "
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
                    console.log('Listener added for event " . ((empty($this->jsonSelector) || $this->event == $this->jsonSelector) ? $this->event : $this->event . '/' . $this->jsonSelector) . ", listener: ', listener);
                    eventListeners[string] = true;
                }

            }
        ";
        $this->view->params['listenEvents'][] = [$this->event, $register];

        if(\Yii::$app->request->isAjax){
            $this->view->registerJs($register);
        }
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

}
