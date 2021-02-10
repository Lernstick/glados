<?php

namespace app\assets;

use yii\web\AssetBundle;

class SearchAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [];
    public $js = [
        'js/search.js',
    ];
    public $depends = [];
}
