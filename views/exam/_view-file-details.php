<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\widgets\Pjax;
use miloschuman\highcharts\Highcharts;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $model app\models\Exam */

?>

<?php Pjax::begin(['id' => 'exam-view-file-details']); ?>

<div class="exam-view-file-details">

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-bordered table-hover'],
        'rowOptions' => function($data) {
            return null;
        },

        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'mode',
            'date:date',
            'time:time',
            'owner',
            'group',
            'compressed_size:shortSize',
            'path',
        ],
    ]) ?>

</div>

<?php Pjax::end(); ?>

