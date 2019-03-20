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

    <div class="alert alert-success" role="alert">
        <span class="glyphicon glyphicon-alert"></span>
        <span>For more information, please visit <?= Html::a('Manual / Create single ticket', ['/howto/view', 'id' => 'create-single-ticket.md'], ['class' => 'alert-link']) ?>.</span>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
        'searchModel' => $searchModel,
        'attr' => null,
    ]) ?>

</div>
