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

    <div class="dropdown">
      <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
        <i class="glyphicon glyphicon-list-alt"></i>
        Actions&nbsp;<span class="caret"></span>
      </button>
      <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
        <li>
            <?= Html::a('<span class="glyphicon glyphicon-th-list"></span> Start Daemon', ['create', 'type' => 'daemon']) ?>
        </li>      
        <li>
            <?= Html::a('<span class="glyphicon glyphicon-hdd"></span> Start Backup Daemon', ['create', 'type' => 'backup']) ?>
        </li>
        <li>
            <?= Html::a('<span class="glyphicon glyphicon-globe"></span> Start Download Daemon', ['create', 'type' => 'download']) ?>
        </li>        
        <li>
            <?= Html::a('<span class="glyphicon glyphicon-search"></span> Start Analyzer Daemon', ['create', 'type' => 'analyze']) ?>
        </li>        
      </ul>
    </div>
    <br>

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
                'itemOptions' => ['sort-value' => 'started_at'],
                'layout' => '{items} {summary} {pager}',
            ] ); ?>

        </div>

    <?php ActiveEventField::end(); ?>

</div>
