<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ListView;
use app\components\ActiveEventField;

/* @var $this yii\web\View */
/* @var $model app\models\Exam */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \Yii::t('exams', 'Exams'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$src = Url::to(['ticket/live', 'token' => '{token}']);

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
    }
});
},1000);
SCRIPT;
$this->registerJs($js, \yii\web\View::POS_READY);

?>
<div class="exam-view container">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_nav', [
        'model' => $model,
    ]) ?>

    <p></p>


        <?php $_GET = array_merge($_GET, ['#' => 'tab_monitor']); ?>

        <?= ListView::widget([
            'dataProvider' => $dataProvider,
            'options' => ['class' => 'row'],
            'itemOptions' => ['class' => 'col-xs-6 col-md-3'],
            'itemView' => function ($model, $key, $index, $widget) {

                return '<div class="thumbnail">' . ActiveEventField::widget([
                        'options' => [
                            'tag' => 'img',
                            'alt' => 'No img', #TODO
                            'src' => Url::to(['ticket/live', 'token' => $model->token, '_ts']),
                            'data-time' => intval(microtime(true)),
                            'data-url' => Url::to(['ticket/live', 'token' => $model->token]),
                            'class' => 'live-thumbnail',
                        ],
                        'event' => 'ticket/' . $model->id,
                        'jsonSelector' => 'live',
                        'jsHandler' => 'function(d, s) {
                            // reload image when a new has arrived
                            s.src = "data:image/jpg;base64," + d.base64;
                            $(s).attr("data-time", parseInt(new Date().getTime()/1000));
                        }',
                    ])
                     . '<div class="caption">'
                     . $model->token #TODO
                     . '</div></div>';
            },
            'summaryOptions' => [
                'class' => 'summary col-xs-12 col-md-12',
            ],
            'emptyText' => \Yii::t('ticket', 'No tickets found.'),
            'layout' => '{items} <br>{summary} {pager}',
        ]); ?>


</div>
