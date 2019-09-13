<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Auth */

$this->title = \Yii::t('auth', 'Migrate existing local users to authenticate via {name}', [
    'name' => $model->name,
]);

$this->params['breadcrumbs'][] = ['label' => \Yii::t('auth', 'Authentication Methods'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = \Yii::t('auth', 'Migrate');

?>
<div class="auth-mirgate">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render($model->obj->form . '_migrate', [
        'model' => $model,
    ]) ?>

</div>
