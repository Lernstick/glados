<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\User */
/* @var $searchModel app\models\UserSearch */

$this->title = \Yii::t('users', 'Edit User: {id}', [ 'id' => $model->id ]);
$this->params['breadcrumbs'][] = ['label' => \Yii::t('users', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = \Yii::t('users', 'Edit');
?>
<div class="user-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'searchModel' => $searchModel,
    ]) ?>

</div>
