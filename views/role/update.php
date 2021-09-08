<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\User */
/* @var $dataProvider ArrayDataProvider */

$this->title = \Yii::t('users', 'Edit Role: {id}', [ 'id' => $model->name ]);
$this->params['breadcrumbs'][] = ['label' => \Yii::t('user', 'Roles'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->name]];
$this->params['breadcrumbs'][] = \Yii::t('users', 'Edit');
?>
<div class="role-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'dataProvider' => $dataProvider,
    ]) ?>

</div>
