<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Ticket */
/* @var $searchModel app\models\TicketSearch */

$this->title = 'Create Ticket';
$this->params['breadcrumbs'][] = ['label' => 'Tickets', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ticket-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'searchModel' => $searchModel,
    ]) ?>

</div>
