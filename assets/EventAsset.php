<?php

namespace app\assets;

use yii\web\AssetBundle;

class EventAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
    ];
    public $js = [
        'js/events.js',
    ];
    public $depends = [
    ];
}
