<?php

/* @var $this yii\web\View */

use yii\helpers\Html;
use yii\helpers\Markdown;

$this->title = 'Howto: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Howtos', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
?>
<div class="howto-view">

    <h1><?= Html::encode($model->title) ?></h1>

    <?= Markdown::process($model->content, 'gfm'); ?>

</div>
