<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\ListView;
use app\components\ActiveEventField;

/* @var $this yii\web\View */
/* @var $searchModel app\models\RestoreSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

?>

<?= DetailView::widget([
    'model' => $ticketModel,
    'attributes' => [
        [
            'attribute' => 'restore_lock',
            'format' => 'raw',
            'value' =>  ActiveEventField::widget([
                'content' => $ticketModel->restore_lock,
                'event' => 'ticket/' . $ticketModel->id,
                'jsonSelector' => 'restore_lock',
            ]),
        ],
        [
            'attribute' => 'restore_state',
            'format' => 'raw',
            'value' =>  ActiveEventField::widget([
                'content' => yii::$app->formatter->format($ticketModel->restore_state, 'ntext'),
                'event' => 'ticket/' . $ticketModel->id,
                'jsonSelector' => 'restore_state',
            ]),
        ],
    ],
]) ?>

<?= ListView::widget([
    'dataProvider' => $dataProvider,
    'options' => ['id' => 'restores-accordion', 'class' => 'panel-group'],
    'itemOptions' => ['class' => 'panel panel-default'],
    'itemView' => '_item',
    'emptyText' => 'No restores found.',
    'layout' => '{items} <br>{summary} {pager}',
]); ?>

