<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\components\ActiveEventField;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */

$this->registerJs('window.location.href = "#wxbrowser:resize:800x600"');

$this->title = \Yii::t('client', 'Hand-in Exam');

?>

<div class="finish-s1-view">

    <?= $this->render('_finish_s1', [
        'model' => $model,
        'finish' => true,
    ]) ?>

</div>