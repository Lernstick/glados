<?php

use yii\helpers\Url;
use yii\helpers\Html;
use app\components\VideoJsWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */

# the m3u8 video file
$file = \Yii::$app->params['backupPath'] . '/' . $model->token . '/Schreibtisch/out/video.m3u8';

if (file_exists($file)) {

    $url = Url::to(['backup/file', 'ticket_id' => $model->id, 'path' => '/Schreibtisch/out/video.m3u8']);
    $js = <<< SCRIPT
videojs.Hls.xhr.beforeRequest = function(options) {
  options.uri = options.uri.replace(/backup\/file\/video(\d+)/, 'backup/file/<?= $model->id?>?path=%2FSchreibtisch%2Fout%2Fvideo$1');
  return options;
};

var player = videojs('video-container', {
    liveui: true
});

SCRIPT;

    // Initialze the videojs player
    $this->registerJs($js, \yii\web\View::POS_READY);

?>

    <button onClick="
    //videojs.log.level('all');
    videojs.Hls.xhr.beforeRequest = function(options) {
      options.uri = options.uri.replace(/backup\/file\/video(\d+)/, 'backup/file/<?= $model->id?>?path=%2FSchreibtisch%2Fout%2Fvideo$1');
      return options;
    };

    var player = videojs('video-container', {
        liveui: true
    });
    //var ModalDialog  = videojs.getComponent('ModalDialog');
    /*var modal = new ModalDialog(player, {
        content: 'Please wait while the video refreshes.',
        temporary: false
    });
    modal.name_ = 'Modal';
    player.addChild(modal);*/

    player.on('play', function(){
        var player = this;
        console.log('----------------play triggered---------------', player);
    });
    ">init</button>
    <?= VideoJsWidget::widget([
        'options' => [
            'id' => 'video-container',
            'class' => 'video-js vjs-fluid vjs-default-skin vjs-big-play-centered',
            //'poster' => "http://www.videojs.com/img/poster.jpg",
            'controls' => true,
            'preload' => 'auto',
            'fluid' => true,
            'responsive' => true,
        ],
        'tags' => [
            'source' => [
                [
                    'src' => Url::to(['backup/file', 'ticket_id' => $model->id, 'path' => '/Schreibtisch/out/video.m3u8']),
                    'type' => 'application/x-mpegURL',
                ],
            ],
        ]
    ]); ?>

<?php

} else {

?>

    <div class="row">
        <div class="col-sm-12 text-center">
            <i class="glyphicon glyphicon-warning-sign"></i>&nbsp;<span><?= Yii::t('ticket', 'No video file(s) found.') ?></span>
            <br><br>
            <a class="btn btn-default" onClick='$.pjax.reload({container:"#screencapture"});'><i class="glyphicon glyphicon-refresh"></i>&nbsp;<?= Yii::t('app', 'Reload') ?></a>
        </div>
    </div>

<?php

}

?>