<?php

namespace app\assets;

use yii\web\AssetBundle;

class EventSourcePolyfillAsset extends AssetBundle
{

    public $sourcePath = '@npm/event-source-polyfill/src';
    public $js = [
        'eventsource.min.js',
    ];
}
