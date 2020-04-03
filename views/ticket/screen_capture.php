<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\DetailView;
use app\components\VideoJsWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */

# the m3u8 video file
if ($model->screencapture !== null) {
    $js = <<< SCRIPT
var player = videojs('video-container', {
    liveui: true
});
SCRIPT;

    // Initialze the videojs player
    $this->registerJs($js, \yii\web\View::POS_READY);

?>

<?= DetailView::widget([
    'model' => $model,
    'attributes' => [
        'sc_size:shortSize',
    ],
]) ?>

    <button onClick="
    //videojs.log.level('all');

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
                    'src' => Url::to(['screencapture/view', 'id' => $model->id, 'file' => 'master.m3u8']),
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