<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\components\ActiveEventField;

/* @var $this yii\web\View */
/* @var $model app\models\Daemon */

$this->title = $model->description . ' (' . $model->pid . ')';
$this->params['breadcrumbs'][] = ['label' => 'Daemons', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="daemon-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="dropdown">
      <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
        <i class="glyphicon glyphicon-list-alt"></i>
        Actions&nbsp;<span class="caret"></span>
      </button>
      <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
        <li>
            <?= Html::a('<span class="glyphicon glyphicon-stop"></span> Stop', ['stop', 'id' => $model->id]) ?>
        </li>
        <li>
            <?= Html::a('<span class="glyphicon glyphicon-flash"></span> Kill', ['kill', 'id' => $model->id], [
                'data' => [
                    'confirm' => 'Are you sure you want to kill this process?',
                ],
            ]) ?>
        </li>        
      </ul>
    </div>
    <br>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            [
                'attribute' => 'id',
                'visible' => YII_ENV_DEV,
                'captionOptions' => ['class' => 'dev_item'],
            ],
            'pid',
            'running:boolean',
            [
                'attribute' => 'uuid',
                'visible' => YII_ENV_DEV,
                'captionOptions' => ['class' => 'dev_item'],
            ],
            'description',
            'started_at',
            [
                'attribute' => 'state',
                'format' => 'raw',
                'value' =>  ActiveEventField::widget([
                    'content' => $model->state,
                    'event' => 'daemon/' . $model->pid,
                    'jsonSelector' => 'state',
                ]),
            ],
            'alive:timeago',
        ],
    ]) ?>

</div>
