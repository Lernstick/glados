<?php

/* @var $this yii\web\View */

use yii\helpers\Html;
use yii\helpers\Markdown;

$this->title = $model->title;

// froces wxbrowser to resize the window
$js = <<< 'SCRIPT'
window.location.href = '#wxbrowser:resize:850x600'
SCRIPT;
$this->registerJs($js);

?>

<div class="howto-view markdown-view">
    
    <h2><?= Html::encode($model->title) ?></h2>

    <?= Markdown::process($model->content, 'gfm'); ?>

</div>
