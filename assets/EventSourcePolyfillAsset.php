<?php

namespace app\assets;

use yii\web\AssetBundle;

/**
 * This is a polyfill for internet explorer or native edge
 */
class EventSourcePolyfillAsset extends AssetBundle
{

    public $sourcePath = '@npm/event-source-polyfill/src';
    public $js = [
        'eventsource.min.js',
    ];
}
