<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */

$this->title = $model->token;
?>
<div class="ticket-view">

<div class="panel panel-default">
  <div class="panel-heading">Exam</div>
  <div class="panel-body">
  <table class="table">
    <tbody>
      <tr>
        <td>Exam Subject</td>
        <td><?= Html::encode($model->exam->subject) ?></td>
      </tr>
      <tr>
        <td>Exam Name</td>
        <td><?= Html::encode($model->exam->name) ?></td>
      </tr>
    </tbody>
  </table>
</div>
</div>


<div class="panel panel-danger">
  <div class="panel-heading">Your Token</div>
  <div class="panel-body"><h2 class="text-center"><?= Html::encode($model->token) ?></h2></div>
</div>

<div class="panel panel-default">
  <div class="panel-heading">Name</div>
  <div class="panel-body"><?= $model->test_taker ? Html::encode($model->test_taker) : '&nbsp;' ?></div>
</div>

<div class="panel panel-default">
  <div class="panel-heading">Signature</div>
  <div class="panel-body">&nbsp;</div>
</div>



<br>
<div class="panel panel-default">
  <div class="panel-heading">Barcode</div>
  <div class="panel-body">
    <barcode code="<?= Html::encode($model->token) ?>" type="C128A" />
  </div>
</div>

</div>

