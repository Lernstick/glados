<?php

/* @var $this yii\web\View */

use yii\helpers\Html;
use yii\helpers\Markdown;

$this->title = 'Howto';
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
?>
<div class="howto-view">

    <h1><?= Html::encode($model->title) ?></h1>

    <?= Markdown::process($model->content, 'gfm'); ?>

</div>
