<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Auth */

$this->title = \Yii::t('auth', 'Create new Authentication Method - Step {step}', [
	'step' => $step,
]);
$this->params['breadcrumbs'][] = ['label' => \Yii::t('auth', 'Authentication Methods'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="auth-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render($model->obj->form, [
        'model' => $model,
    ]) ?>

</div>
