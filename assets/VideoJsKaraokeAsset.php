<?php

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Asset bundle for the karaoke-style subtitles extension for videojs
 */
class VideoJsKaraokeAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [];
    public $js = [
        'js/videojs.karaoke.js',
    ];
    public $depends = [
        'app\assets\VideoJsCoreAsset'
    ];
}