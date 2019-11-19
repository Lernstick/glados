<?php

use yii\helpers\Html;
use yii\widgets\Pjax;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\Auth */
/* @var $searchmodel app\models\AuthSearch */
/* @var $form yii\widgets\ActiveForm */

$from = $model->fromModel;
$to = $model->toModel;

?>

<div class="row">
    <div class="col-md-12 help-block">
        <?= \Yii::t('auth', 'The following users are associated to {from}. Selected users will be migrated from {from} to the authentication method {to}.', [
            'from' => is_object($from) ? $from->name : $from,
            'to' => $to->name,
        ]); ?>
    </div>
</div>