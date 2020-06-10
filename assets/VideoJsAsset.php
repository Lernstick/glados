<?php

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Asset bundle for videojs Widget
 */
class VideoJsAsset extends AssetBundle
{
    public $depends = [
        'app\assets\VideoJsCoreAsset',
        'app\assets\VideoJsKaraokeAsset',
    ];
}
