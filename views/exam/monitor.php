<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ListView;
use yii\bootstrap\Modal;

/* @var $this yii\web\View */
/* @var $model app\models\Exam */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \Yii::t('exams', 'Exams'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$title = \Yii::t('ticket', 'Currently behind live');

$js = <<< SCRIPT
// reload images if no new image for 10 seconds
setInterval(function(){
    $('img.live-thumbnail').each(function() {
    var img = $(this);
    var src = img.attr('data-url');
    var time = parseInt(img.attr("data-time"));
    var now = parseInt(new Date().getTime()/1000);
    if (now - time > 10) {
        img.attr('src', src + '?_ts=' + now);
        img.attr("data-time", now);
        img.next().find(">:first-child").removeClass("live");
        img.next().find(">:first-child").attr("title", "$title");
    }
});
},1000);

$('#galleryModal').on('show.bs.modal', function(e) {
    $('#galleryModal img').attr("src", "");
    $('#galleryModal img').attr("src", $(e.relatedTarget).data('src') + "&" + new Date().getTime());
});
SCRIPT;
$this->registerJs($js, \yii\web\View::POS_READY);

?>

<div class="exam-view container">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_nav', [
        'model' => $model,
        'tab' => 'monitor',
    ]) ?>

    <p></p>

    <div class="tab-content">

        <div id="monitor" class="tab-pane fade">

            <?php $_GET = array_merge($_GET, ['#' => 'tab_monitor']); ?>

            <?= ListView::widget([
                'dataProvider' => $dataProvider,
                'options' => ['class' => 'row'],
                'itemOptions' => ['class' => 'col-xs-6 col-md-3 live-overview-item-container'],
                'itemView' => '_live_overview_item',
                'summaryOptions' => [
                    'class' => 'summary col-xs-12 col-md-12',
                ],
                'emptyText' => \Yii::t('ticket', 'No tickets found.'),
                'layout' => '{items} <br>{summary} {pager}',
            ]); ?>

        </div>

    </div>

<?php Modal::begin([
    'id' => 'galleryModal',
    'header' => false,
    'footer' => Html::Button(\Yii::t('app', 'Close'), ['data-dismiss' => 'modal', 'class' => 'btn btn-default']),
    'size' => \yii\bootstrap\Modal::SIZE_LARGE
]); ?>


    <div class="live-overview-detail">
        <span class="live-overview-detail-title"><i class="gly-spin glyphicon glyphicon-cog"></i> Please wait...</span>
        <img src="#" alt="Image not found.">
    </div>

<?php Modal::end(); ?>

</div>
