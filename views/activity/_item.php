<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use app\components\ActiveEventField;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ActivitySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $last_visited */

?>

<?php Pjax::begin() ?>

<div class="well well-sm text-center hidden" role="alert">
    <?= Html::a(
        '' . ActiveEventField::widget([
            'options' => [
                'tag' => 'span',
            ],
            'content' => '0',
            'event' => isset($ticket) ? 'ticket/' . $ticket->id : 'newActivities',
            'jsonSelector' => 'newActivities',
            'jsHandler' => 'function(d, s){
                s.innerHTML = eval(s.innerHTML + d);
                s.parentNode.parentNode.classList.remove("hidden");
            }',       
        ]) . '&nbsp;' . \Yii::t('activity', 'new activities; click to load'),
        isset($ticket) ? ['view', 'id' => $ticket->id] : ['index'],
        [
            'class' => 'alert-link',
            'onClick' => '$.pjax.reload({container: "#activities", async:false});'
        ]
    ); ?>
</div>

<?php $x = GridView::begin([
    'dataProvider' => $dataProvider,
    'rowOptions' => function($model) {
        return $model->date < $model->lastvisited ? null : ['class' => 'warning' ];
    },
    'columns' => [
        'date:timeago',
        'description:links',
    ],
    'layout' => '{items} {pager}',
    'headerRowOptions' => [ 'style' => 'width:0%; display:none' ],
    'emptyText' => \Yii::t('activity', 'No activities found.'),
    'pager' => [
        'class' => app\widgets\CustomPager::className(),
        'selectedLayout' => Yii::t('app', '{selected} <span style="color: #737373;">items</span>'),
    ],
]); ?>

<?php GridView::end() ?>
<?php Pjax::end() ?>
