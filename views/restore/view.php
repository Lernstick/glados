<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Restore */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Restores', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="restore-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Edit', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'startedAt',
            'finishedAt',
            'ticket_id',
            'source',
            'target',
            'restoreDate',
        ],
    ]) ?>

</div>
