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

    <p>

        <?= Html::a('Stop', ['stop', 'id' => $model->id], ['class' => 'btn btn-danger']) ?>
        <?= Html::a('Kill', ['kill', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to kill this process?',
            ],
        ]) ?>

    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'pid',
            'uuid',
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
        ],
    ]) ?>

</div>
