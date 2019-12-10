<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Auth */
/* @var $searchModel app\models\UserSearch */

$this->title = \Yii::t('auth', 'Edit Authentication Method {name} of type {type}', [
	'name' => $model->name,
	'type' => $model->obj->typeName,
]);

$this->params['breadcrumbs'][] = ['label' => \Yii::t('auth', 'Authentication Methods'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = \Yii::t('auth', 'Edit');
?>
<div class="auth-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render($model->obj->form, [
        'model' => $model,
        'searchModel' => $searchModel,
    ]) ?>

</div>
