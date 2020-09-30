<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\components\ActiveEventField;

/* @var $this yii\web\View */
/* @var $stream app\models\EventStream */

$this->title = 'Listen to events';
$this->params['breadcrumbs'][] = $this->title;

if ($stream->isNewRecord) {
    $stream->listenEvents = 'newActivities,runningDaemons,meta,test/1234';
}

$js = <<< 'SCRIPT'
$('.clear').each(function () {
    $(this).click(function(){
        var textarea = $(this).siblings('textarea');
        textarea.val('');
        textarea.data('lines', 1);
    });
});

SCRIPT;
// Register tooltip/popover initialization javascript
$this->registerJs($js, \yii\web\View::POS_READY);


?>
<div class="event-form">
    <div class="row">
        <div class="col-md-12">
            <?php $form = ActiveForm::begin(); ?>
            <?= $form->field($stream, 'listenEvents')->textInput() ?>
            <div class="form-group">
                <?= Html::submitButton('Listen', ['class' => 'btn btn-primary']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
    <div class="progress" style="height:4px;">
        <?= ActiveEventField::widget([
            'options' => [
                'class' => 'progress-bar',
                'role' => 'progressbar',
                'aria-valuenow' => '0',
                'aria-valuemin' => '0',
                'aria-valuemax' => '100',
            ],
            'content' => '',
            'event' => 'meta',
            'jsonSelector' => '*',
            'jsHandler' => 'function(d, s){
                if ("timeLimit" in d) {
                    $(s).css({"width":"100%","transition": d.timeLimit + "s"})
                }
                if (d.state == "event stream finished") {
                    $(s).css({"width":"0%","transition": "0s"})
                }
            }',
        ]); ?>
    </div><hr>
    <div class="row">
        <?php
        foreach (explode(',', $stream->listenEvents) as $event) {
            echo '<div class="form-group col-md-6">';
            echo '<label class="control-label" for="id">' . $event . '</label>';
            echo '<button class="clear">clear</button>';
            echo '<div class="hint-block"># [since clear]; delta since last [s]; datetime [d.M.Y, h:m:s.ms]; data [json]</div>';
            echo ActiveEventField::widget([
                'options' => [
                    'tag' => 'textarea',
                    'class' => 'form-control',
                    'data-last' => intval(microtime(true))*1000,
                    'data-lines' => 1,
                ],
                'content' => '',
                'event' => $event,
                'jsonSelector' => '*',
                'jsHandler' => 'function(d, s){
                    now = new Date();
                    var s = $(s);
                    var last = parseInt(s.data("last"));
                    var lines = parseInt(s.data("lines"));
                    var milli = now.getTime();
                    var delta = (milli - last)/1000;
                    var txt = "#" + lines + "; " + delta + "; " + now.toLocaleString() + "." + now.getMilliseconds() + "; " + JSON.stringify(d) + "\n";
                    s.val(s.val() + txt);
                    s.scrollTop(s[0].scrollHeight);
                    s.data("last", milli);
                    s.data("lines", lines+1);
                }',
            ]);
            echo '</div>';
        }
        ?>
    </div>
</div>
