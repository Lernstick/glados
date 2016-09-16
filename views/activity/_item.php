<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ActivitySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $last_visited */

?>

<?php Pjax::begin() ?>
<?php $x = GridView::begin([
    'dataProvider' => $dataProvider,
    'rowOptions' => function($model) {
        return $model->date < $model->lastvisited ? null : ['class' => 'warning' ];
    },
    'columns' => [

        'date',
        [
            'attribute' => 'description',
            'format' => 'raw',
            'value' => function($model) {
                return 'Ticket ' .
                    Html::a(
                        $model->ticket->id,
                        ['ticket/view', 'id' => $model->ticket->id],
                        ['data-pjax' => 0]
                    ) .
                    ': ' . $model->description;
            },
        ],

    ],
    'layout' => '{items} {pager}',
    'headerRowOptions' => [ 'style' => 'width:0%; display:none' ],
    'emptyText' => 'No activities found.',
]); ?>

<?php GridView::end() ?>
<?php Pjax::end() ?>
