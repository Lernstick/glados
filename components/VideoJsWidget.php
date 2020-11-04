<?php

namespace app\components;

use \yii\base\Widget;
use app\assets\VideoJsAsset;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\base\InvalidConfigException;

/**
 * The yii2-videojs-widget is a Yii 2 wrapper for the video.js
 * See more: http://www.videojs.com/
 * @see: https://github.com/wbraganca/yii2-videojs-widget
 */
class VideoJsWidget extends Widget
{
    /**
     * @var array options array for the <video-js> html tag
     * @see https://www.yiiframework.com/doc/api/2.0/yii-helpers-basehtml#beginTag()-detail
     */
    public $options = [];

    /**
     * @var array videojs options array
     * @see https://docs.videojs.com/tutorial-options.html
     */
    public $jsOptions = [];

    /**
     * @var array
     */
    public $tags = [];

    /**
     * @var string the default class(es) of the video-js element
     */
    public $defaultClass = 'video-js vjs-default-skin vjs-big-play-centered';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->initOptions();
        $this->registerAssets();
    }

    /**
     * Initializes the widget options
     */
    protected function initOptions()
    {
        if (!isset($this->options['id'])) {
            $this->options['id'] = 'videojs-' . $this->getId();
        }

        if (!isset($this->options['class'])) {
            $this->options['class'] = $this->defaultClass;
        }
    }

    /**
     * Registers the needed assets
     */
    public function registerAssets()
    {
        $view = $this->getView();
        $obj = VideoJsAsset::register($view);

        echo "\n" . Html::beginTag('video-js', $this->options);
        if (!empty($this->tags) && is_array($this->tags)) {
            foreach ($this->tags as $tagName => $tags) {
                if (is_array($this->tags[$tagName])) {
                    foreach ($tags as $tagOptions) {
                        $tagContent = '';
                        if (isset($tagOptions['content'])) {
                            $tagContent = $tagOptions['content'];
                            unset($tagOptions['content']);
                        }
                        echo "\n" . Html::tag($tagName, $tagContent, $tagOptions);
                    }
                } else {
                    throw new InvalidConfigException("Invalid config for 'tags' property.");
                }
            }
        }
        echo "\n" . Html::endTag('video-js');

        if (!empty($this->jsOptions)) {
            $js = 'videojs("' . $this->options['id'] . '", ' . Json::encode($this->jsOptions). ');';
            $view->registerJs($js);
        }
    }
}
