<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\DetailView;
use app\components\VideoJsWidget;
use yii\bootstrap\Modal;
use yii\widgets\Pjax;

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

}

?>

<?= DetailView::widget([
    'model' => $model,
    'attributes' => [
        'sc_size:shortSize',
    ],
]) ?>

    <div class="panel panel-default">
        <div class="panel-heading">
            <span><?= \Yii::t('ticket', 'Screen Capture'); ?></span>
            <div class="pull-right">
                <div class="btn-group">
                    <a href="#" class="dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="glyphicon glyphicon-list-alt"></span> <?= \Yii::t('ticket', 'Actions'); ?><span class="caret"></span>
                    </a>            
                    <ul class="dropdown-menu dropdown-menu-right">
                        <li>
                            <?= Html::a(
                                '<span class="glyphicon glyphicon-paperclip"></span> '. \Yii::t('ticket', 'Show Log File'),
                                Url::to([
                                    'screencapture/log',
                                    'ticket_id' => $model->id,
                                ]),
                                [
                                    'id' => 'screencapture-log-show',
                                    'title' => \Yii::t('ticket', 'Show screencapture log')
                                ]
                            ); ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div>

            <?php if ($model->screencapture !== null) { ?>

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

            <?php } else { ?>
                <br>
                <div class="row">
                    <div class="col-sm-12 text-center">
                        <i class="glyphicon glyphicon-warning-sign"></i>&nbsp;<span><?= Yii::t('ticket', 'No video file(s) found.') ?></span>
                        <br><br>
                        <a class="btn btn-default" onClick='$.pjax.reload({container:"#screencapture"});'><i class="glyphicon glyphicon-refresh"></i>&nbsp;<?= Yii::t('app', 'Reload') ?></a>
                    </div>
                </div>
                <br>
            <?php } ?>

        </div>
    </div>

<?php

$screencaptureLogButton = "
    $('#screencapture-log-show').click(function(event) {
        event.preventDefault();
        $('#screencaptureLogModal').modal('show');
        $.pjax({url: this.href, container: '#screencaptureLogModalContent', push: false, async:false})
    });
";
$this->registerJs($screencaptureLogButton);

Modal::begin([
    'id' => 'screencaptureLogModal',
    'header' => '<h4>' . \Yii::t('ticket', 'Screencapture Log') . '</h4>',
    'footer' => Html::Button(\Yii::t('ticket', 'Close'), ['data-dismiss' => 'modal', 'class' => 'btn btn-default']),
    'size' => \yii\bootstrap\Modal::SIZE_LARGE
]);

    Pjax::begin([
        'id' => 'screencaptureLogModalContent',
    ]);
    Pjax::end();

Modal::end();

?>
