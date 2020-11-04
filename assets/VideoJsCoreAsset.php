<?php

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Asset bundle for videojs core
 */
class VideoJsCoreAsset extends AssetBundle
{
    public $sourcePath = '@vendor/videojs/video.js';
    public $css = [
    	'video-js.min.css',
    ];
    public $js = [
        'video.min.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}
