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
    liveui: true,
    html5: {
        nativeTextTracks: false
    }
});


player.on('loadeddata', function() {
    textTracks = player.textTracks();
    if (1 in textTracks) {
        var track = textTracks[1];
        track.on('cuechange', function (e) {
            console.log("cuechange called");
            var ac = track.activeCues;
            if (0 in ac) {
                console.log("inner1 block");
                var cue = ac[0];
                if (typeof cue !== 'undefined') {
                    console.log("inner2 block", cue.startTime, cue.endTime, cue.text);
                    var times = cue.text.match(/([0-9]+\:[0-9]+\:[0-9]+\.[0-9]+)/g);
                    var texts = cue.text.split(/\<[0-9]+\:[0-9]+\:[0-9]+\.[0-9]+\>/);
                    var cueStartTimes = [ cue.startTime ];
                    var cueTexts = [ "<c.now>" + texts[0] + "</c><c.future>" + texts.slice(1).join("") + "</c>" ];
                    var cueEndTimes = [];
                    console.log(times, texts, cue)
                    if (times !== null) {
                        for (i = 0; i < times.length; i++) {
                            a = times[i].match(/([0-9]+)\:([0-9]+)\:([0-9]+)\.([0-9]+)/);
                            tot_ms = parseInt(a[4])/1000 + parseInt(a[3]) + parseInt(a[2])*60 + parseInt(a[1])*60*60;
                            cueStartTimes.push(tot_ms);
                            //cueTexts.push("<c.past>" + cueTexts[cueTexts.length - 1] + "</c><c.now>" + texts[i+1]) + "</c>";
                            var past = texts.slice(0, i+1).join("");
                            var now = texts[i+1];
                            var future = texts.slice(i+2).join("");
                            cueTexts.push("<c.past>" + past + "</c><c.now>" + now + "</c><c.future>" + future + "</c>");
                        }

                        for (i = 0; i < cueStartTimes.length; i++) {
                            if (i+1 in cueStartTimes) {
                                cueEndTimes.push(cueStartTimes[i+1]);
                                var end = cueStartTimes[i+1];
                            } else {
                                cueEndTimes.push(cue.endTime);
                                var end = cue.endTime;
                            }
                        }
                        track.removeCue(cue);
                        for (i = 0; i < cueStartTimes.length; i++) {
                            track.addCue(new window.VTTCue(cueStartTimes[i], cueEndTimes[i], cueTexts[i]));
                            console.log("adding cue", cueStartTimes[i], cueEndTimes[i], cueTexts[i]);
                        }
                    } else {
                        console.log(cue.startTime, cue.endTime, cue.text);
                    }
                }
            }
        });
        track.mode = 'hidden';
    }
});


/*
// simulate karaoke style subtitles (mozilla's vtt.js seems not to support them)
player.on('loadeddata', function() {
    textTracks = player.textTracks();
    if (1 in textTracks) {
        var track = textTracks[1];
        track.on('cuechange', function (e) {
            var ac = track.activeCues;
            if (0 in ac) {
                var cue = ac[0];
                if (typeof cue !== 'undefined') {
                    var time = cue.text.match(/\<[0-9]+\:[0-9]+\:[0-9]+\.[0-9]+\>/);
                    var texts = cue.text.split(time);
                    //console.log(time, texts)
                    if (time !== null) {
                        [tot, h, m, s, ms] = time[0].match(/\<([0-9]+)\:([0-9]+)\:([0-9]+)\.([0-9]+)\>/);
                        int = parseInt(ms)/1000 + parseInt(s) + parseInt(m)*60 + parseInt(h)*60*60;
                        var start = cue.startTime;
                        var end = cue.endTime;
                        track.removeCue(cue);
                        track.addCue(new window.VTTCue(start, int, texts[0]));
                        track.addCue(new window.VTTCue(int, end, texts[0] + texts[1]));
                    } else {
                        console.log(texts[0])
                        //$(".js-keylogger__log").append(texts[0] + "<br>");
                    }
                }
            }
        });
        track.mode = 'hidden';
    }
});
*/

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
        <div class="row">

            <?php if ($model->screencapture !== null) { ?>

                <div class="col-md-12">
                <?= VideoJsWidget::widget([
                    'options' => [
                        'id' => 'video-container',
                        'class' => 'video-js vjs-fluid vjs-default-skin vjs-big-play-centered',
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
                        /*'track' => [
                            [
                                'src' => Url::to(['screencapture/view', 'id' => $model->id, 'file' => 'subtitles.m3u8']),
                                'kind' => 'captions',
                                'label' => 'english',
                                'srclang' => 'en',
                            ],
                        ],*/
                    ]
                ]); ?>
                </div>

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
