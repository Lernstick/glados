<?php

use app\assets\EventAsset;
use yii\helpers\Url;

/* @var $this yii\web\View */

EventAsset::register($this);
$script = '';

if(extension_loaded('inotify') && isset($this->params['listenEvents'])){

    foreach($this->params['listenEvents'] as $event){
        $e[] = $event[0];
        $script .= $event[1];
    }
    $e = implode(',', array_unique($e));;

}

if(isset($this->params['listenFifos'])){

    foreach($this->params['listenFifos'] as $fifo){
        $f[] = $fifo[0];
        $script .= $fifo[1];
    }
    $f = implode(',', array_unique($f));

}

$this->registerJs("event = new EventStream('" . 
    Url::to([
        'event/stream',
        'listenEvents' => isset($e) ? $e : null,
        'listenFifos' => isset($f) ? $f : null,
    ]) . 
"');" . PHP_EOL . 
"eventListeners = [];");

$this->registerJs($script);

?>
