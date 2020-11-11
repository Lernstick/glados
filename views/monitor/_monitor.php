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
$n = $model::monitor_idle_time();

// reload images if no new image for [[monitor_idle_time()]] seconds
$js = <<< SCRIPT
function check_images() {
    $('img.live-thumbnail').each(function() {
        var img = $(this);
        var src = img.attr('data-url');
        var then = parseFloat(img.attr("data-time"));
        var now = parseFloat(new Date().getTime()/1000);
        if (now - then > $n) {
            img.attr('src', src + '?_ts=' + parseInt(now));
            img.attr("data-time", now);
            img.siblings("a").find(".live-indicator").removeClass("live");
            img.siblings("a").find(".live-indicator").attr("title", "$title");
        }
    });
}
setInterval(check_images,1000);

// ensures this works for some older browsers
MutationObserver = window.MutationObserver || window.WebKitMutationObserver || window.MozMutationObserver;

$('img.live-thumbnail').each(function(){
    var img = this;
    new MutationObserver(function() {
        $("<img/>").on('load', function() {
            // succesful loaded image
            $(img).show();
            $(img).next("div").hide();
        }).on('error', function() {
            // error loading image
            $(img).hide();
            $(img).next("div").show();
        }).attr("src", $(img).attr("src"));
    }).observe(img, {
        attributes:true,
        attributeFilter:["src"]
    });
});

check_images();

$('#galleryModal').on('show.bs.modal', function(e) {
    var el = $(e.relatedTarget).parent().children('img').first();
    $('#galleryModal object').attr("data", "");
    $('#galleryModal object').attr("data", el.data('src') + "&" + new Date().getTime());
});
SCRIPT;

echo ActiveEventField::widget([
    'event' => 'exam/' . $model->id,
    'jsonSelector' => 'runningTickets',
    'jsHandler' => 'function(d, s) {
        $("#reload").click();
    }',
]);

Pjax::begin([
    'id' => 'live_monitor'
]);

/**
 * A dummy event in the group "monitor", such that if no event in the group "monitor" is active
 * the new js event handlers won't be registered. Also they won't be unregistered if the last
 * element is disappearing from the view. Using this a event of type "monitor" will always be 
 * present in the view.
 */
echo ActiveEventField::widget([
    'event' => 'monitor:exam/' . $model->id,
    'jsonSelector' => 'dummy',
    'jsHandler' => 'function(d, s){}', // do nothing
]);

?>

<div class="row">
    <div class="col-sm-9">
        <span><?= \Yii::t('monitor', 'Only tickets in {state} state will be shown here.', [
            'state' => '<span data-state="1" class="label view--state">' . Yii::t('ticket', 'Running') . '</span>'
        ]); ?></span>
    </div>
    <div class="col-sm-3 text-right">
        <!-- Don't remove this button! Make it invisible instead. -->
        <a class="btn btn-default" id="reload" href=""><i class="glyphicon glyphicon-refresh"></i>&nbsp;<?= Yii::t('app', 'Reload') ?></a>
    </div>
</div>
    <?php $this->registerJs($js, \yii\web\View::POS_READY); ?>

    <?= ListView::widget([
        'dataProvider' => $dataProvider,
        'options' => ['class' => 'row'],
        'itemOptions' => ['class' => 'col-xs-6 col-md-3 live-overview-item-container'],
        'itemView' => '_live_overview_item',
        'summaryOptions' => [
            'class' => 'summary col-xs-12 col-md-12',
        ],
        'emptyText' => \Yii::t('ticket', 'No running exams found.'),
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