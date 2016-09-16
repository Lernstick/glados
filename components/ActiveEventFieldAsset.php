<?php

namespace app\components;

use yii\web\AssetBundle;

class ActiveEventFieldAsset extends AssetBundle
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
