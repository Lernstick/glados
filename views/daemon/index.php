<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ListView;
use yii\widgets\Pjax;
use app\components\ActiveEventField;

/* @var $this yii\web\View */
/* @var $searchModel app\models\DaemonSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Daemons';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="daemon-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?php Pjax::begin([
        'enablePushState' => false,
    ]); ?>
    <p>
        <?= Html::a('Start Backup Daemon', ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    <?php Pjax::end(); ?>

    <?php ActiveEventField::begin([
        'options' => [
        ],
        'event' => 'daemon/*',
        'onStart' => 'function(d, s){$.pjax.reload({container:s});}',
        'onStop' => 'function(d, s){$.pjax.reload({container:s});}',
    ]); ?>

        <div class="exam-monitor">

            <?= ListView::widget( [
                'dataProvider' => $dataProvider,
                'itemView' => '_item',
                'itemOptions' => ['sort-value' => 'started_at']
            ] ); ?>

        </div>

    <?php ActiveEventField::end(); ?>

</div>
