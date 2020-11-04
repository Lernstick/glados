<?php

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Asset bundle for the playlist extension for videojs
 */
class VideoJsPlaylistAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [];
    public $js = [
        'js/videojs.playlist.js',
    ];
    public $depends = [
        'app\assets\VideoJsCoreAsset'
    ];
}