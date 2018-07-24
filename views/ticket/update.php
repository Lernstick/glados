<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */
/* @var $searchModel app\models\TicketSearch */

$this->title = 'Edit Ticket: ' . ' ' . $model->token;
$this->params['breadcrumbs'][] = ['label' => 'Tickets', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->token, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Edit';
?>
<div class="ticket-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'searchModel' => $searchModel,
    ]) ?>

</div>
