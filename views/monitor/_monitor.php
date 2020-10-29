<?php

use yii\helpers\Html;
use yii\widgets\ListView;
use yii\widgets\Pjax;
use yii\bootstrap\Modal;
use yii\helpers\Url;
use app\components\ActiveEventField;

/* @var $this yii\web\View */
/* @var $model app\models\Exam */
/* @var $searchModel app\models\TicketSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$title = \Yii::t('ticket', 'Currently behind live');
$n = $model::MONITOR_IDLE_TIME;

// reload images if no new image for [[MONITOR_IDLE_TIME]] seconds
// reload the whole thing all [[MONITOR_RELOAD_TIME]]
$js = <<< SCRIPT
setInterval(function(){
    $('img.live-thumbnail').each(function() {
        var img = $(this);
        var src = img.attr('data-url');
        var then = parseFloat(img.attr("data-time"));
        var now = parseFloat(new Date().getTime()/1000);
        if (now - then > $n) {
            img.attr('src', src + '?_ts=' + parseInt(now));
            img.attr("data-time", now);
            img.next().find(">:first-child").removeClass("live");
            img.next().find(">:first-child").attr("title", "$title");
        }
    });
},1000);

$('#galleryModal').on('show.bs.modal', function(e) {
    $('#galleryModal object').attr("data", "");
    $('#galleryModal object').attr("data", $(e.relatedTarget).data('src') + "&" + new Date().getTime());    
});
SCRIPT;
$this->registerJs($js, \yii\web\View::POS_READY);

?>

<?= ActiveEventField::widget([
    'event' => 'exam/' . $model->id,
    'jsonSelector' => 'runningTickets',
    'jsHandler' => 'function(d, s) { $.pjax.reload({container: "#live_monitor"}); }',
]); ?>

<div class="row">
    <div class="col-sm-9">
        <span><?= \Yii::t('monitor', 'Only tickets in running state will be shown here.'); ?></span>
    </div>
    <div class="col-sm-3 text-right">
        <a class="btn btn-default" onClick='$.pjax.reload({container:"#live_monitor"});'><i class="glyphicon glyphicon-refresh"></i>&nbsp;<?= Yii::t('app', 'Reload') ?></a>
    </div>
</div>

<?php Pjax::begin([
    'id' => 'live_monitor'
]); ?>

    <?= ListView::widget([
        'dataProvider' => $dataProvider,
        'options' => ['class' => 'row'],
        'itemOptions' => ['class' => 'col-xs-6 col-md-3 live-overview-item-container'],
        'itemView' => '_live_overview_item',
        'summaryOptions' => [
            'class' => 'summary col-xs-12 col-md-12',
        ],
        'emptyText' => \Yii::t('ticket', 'No tickets found.'),
        'emptyTextOptions' => ['class' => 'col-md-12 text-center'],
        'layout' => '{pager} {summary}<br>{items}',
    ]); ?>

<?php Pjax::end(); ?>

<?php Modal::begin([
    'id' => 'galleryModal',
    'header' => false,
    'footer' => Html::Button(\Yii::t('app', 'Close'), ['data-dismiss' => 'modal', 'class' => 'btn btn-default']),
    'size' => \yii\bootstrap\Modal::SIZE_LARGE
]); ?>

    <div class="live-overview-detail">
        <div class="live-overview-detail-loading alert alert-info" role="alert">
            <i class="gly-spin glyphicon glyphicon-cog" aria-hidden="true"></i>
             <?= \Yii::t('app', 'Please wait...'); ?>
        </div>
        <object class="live-overview-detail-img" data="" type="image/jpg">
            <div class="live-overview-detail-error alert alert-danger" role="alert">
                <i class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></i>
                <?= \Yii::t('app', 'The image could not be loaded.'); ?>
            </div>
        </object>
    </div>

<?php Modal::end(); ?>