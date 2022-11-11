<?php

use yii\helpers\Html;

/* @var $this yii\web\View */

?>

<div class="dropdown pagination pagination-page-size nav pull-left">
  <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
    <i class="glyphicon glyphicon-list-alt"></i>
    <?= \Yii::t('daemons', 'Actions') ?>&nbsp;<span class="caret"></span>
  </button>
  <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
    <li>
        <?= Html::a('<span class="glyphicon glyphicon-th-list"></span> ' . \Yii::t('daemons', 'Start Base Process'), [
            'create',
            'type' => 'daemon',
        ], ['class' => 'action']) ?>
    </li>
    <li>
        <?= Html::a('<span class="glyphicon glyphicon-hdd"></span> ' . \Yii::t('daemons', 'Start Backup Process'), [
            'create',
            'type' => 'backup',
        ], ['class' => 'action']) ?>
    </li>
    <li>
        <?= Html::a('<span class="glyphicon glyphicon-globe"></span> ' . \Yii::t('daemons', 'Start Download Process'), [
            'create',
            'type' => 'download',
        ], ['class' => 'action']) ?>
    </li>
    <li>
        <?= Html::a('<span class="glyphicon glyphicon-search"></span> ' . \Yii::t('daemons', 'Start Analyzer Process'), [
            'create',
            'type' => 'analyze',
        ], ['class' => 'action']) ?>
    </li>
    <li>
        <?= Html::a('<span class="glyphicon glyphicon-fire"></span> ' . \Yii::t('daemons', 'Stop All Processes'), [
            'stop',
            'id' => 'ALL',
        ], [
            'class' => 'action',
            'data' => [
                'confirm' => \Yii::t('daemons', 'Are you sure you want to stop ALL processes?'),
            ],
            'data-method' => 'post',
        ]) ?>
    </li> 
  </ul>
</div>