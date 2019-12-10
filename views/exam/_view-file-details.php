<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\widgets\Pjax;
use miloschuman\highcharts\Highcharts;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $model app\models\Exam */
/* @var $type string */

if ($type == "squashfs") {
    $columns = [
        ['class' => 'yii\grid\SerialColumn'],
        'mode',
        'date:date',
        'time:time',
        'owner',
        'group',
        'size:shortSize',
        'path',
    ];
} else if ($type == "zip") {
    $columns = [
        ['class' => 'yii\grid\SerialColumn'],
        'length',
        'method',
        'cmpr',
        'date:date',
        'time:time',
        'crc32',
        'size:shortSize',
        'path',
    ];
}

?>

<?php Pjax::begin(['id' => 'exam-view-file-details']); ?>

<div class="exam-view-file-details">

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-bordered table-hover'],
        'rowOptions' => function($data) {
            return null;
        },
        'columns' => $columns,
    ]) ?>

</div>

<?php Pjax::end(); ?>

