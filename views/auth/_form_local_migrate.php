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

<div class="panel panel-info">
    <div class="panel-heading">
        <?= Html::label(\Yii::t('auth', 'Query for users')) ?>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-12">
                <?= $form->field($model->toModel, 'migrateSearchPattern')->textInput(['maxlength' => true]) ?>
            </div>
        </div>

		<div class="row">
		    <div class="col-lg-5">
		    	<?= Html::submitButton(\Yii::t('auth', 'Query for users'), ['class' => 'btn btn-primary', 'name' => 'query-users-button', 'id' => 'query-users-button']) ?>
		    </div>
			<div class="col-lg-7">
		        <div class="help-block"><?= implode("<br>", $to->debug); ?></div>
		        <div class="has-error"><div class="help-block"><?= $to->error; ?></div></div>
		        <div class="has-success"><div class="help-block"><?= $to->success; ?></div></div>
		    </div>
		</div>

    </div>
</div>