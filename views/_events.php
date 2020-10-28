<?php

use app\assets\EventAsset;
use yii\helpers\Url;
use app\models\EventStream;

/* @var $this yii\web\View */

EventAsset::register($this);
$script = '';

if (extension_loaded('inotify') && isset($this->params['listenEvents']) && isset($this->params['uuid'])) {

    foreach($this->params['listenEvents'] as $event){
        $e[] = $event['name'];
        $script .= $event['register'];
    }
    $e = implode(',', array_unique($e));

    $stream = new EventStream([
        'uuid' => $this->params['uuid'],
        'listenEvents' => $e,
    ]);
    $stream->save();

    $this->registerJs("uuid = '" . $stream->uuid . "';" . PHP_EOL .
        "event = new EventStream('" . 
        Url::to([
            'event/stream',
            'uuid' => $stream->uuid,
    //        'listenEvents' => isset($e) ? $e : null,
        ]) . "');" . PHP_EOL . 
        "eventListeners = {};" . PHP_EOL
    );

    $this->registerJs($script);

}

?>
