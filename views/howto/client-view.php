<?php

/* @var $this yii\web\View */

use yii\helpers\Html;
use yii\helpers\Markdown;

?>

<div class="howto-view markdown-view">
    
    <h2><?= Html::encode('Help') ?></h2>

    <?= Markdown::process($model->content, 'gfm'); ?>

</div>
