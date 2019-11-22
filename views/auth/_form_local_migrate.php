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

<div class="panel panel-danger">
    <div class="panel-heading">
        <i class="glyphicon glyphicon-warning-sign"></i> <?= \Yii::t('auth', 'The following settings should only be used, if you know what you are doing!') ?>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-12">
                <?= $form->field($model->toModel, 'migrateSearchPattern')->textInput(['maxlength' => true]) ?>
            </div>
        </div>

		<div class="row">
		    <div class="col-lg-5">
		    	<?= Html::submitButton(\Yii::t('auth', 'Query for Users'), ['class' => 'btn btn-primary', 'name' => 'query-users-button', 'id' => 'query-users-button']) ?>
		    </div>
			<div class="col-lg-7">
		        <div class="help-block"><?= implode("<br>", $to->debug); ?></div>
		        <div class="has-error"><div class="help-block"><?= $to->error; ?></div></div>
		        <div class="has-success"><div class="help-block"><?= $to->success; ?></div></div>
		    </div>
		</div>

    </div>
</div>

<div class="row">
    <div class="col-md-12 help-block">
        <?= \Yii::t('auth', 'The following users are currently associated to {from}. Selected users will be migrated from {from} to {to}. In the list below, only users that have a local password are listed, because only these users are able to be migrated. This is when the user was created as a local user in the first place.', [
            'from' => is_object($from) ? $from->name : $from,
            'to' => $to->name,
        ]); ?>
    </div>
</div>