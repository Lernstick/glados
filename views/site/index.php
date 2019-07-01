<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\components\ActiveEventField;
use app\models\Ticket;

/* @var $this yii\web\View */

$this->title = 'GLaDOS';
?>
<div class="site-index">

    <div class="col-md-6">
        <div class="well">
            <h1 class="text-center">
            <?= yii::$app->formatter->format($running_exams, 'shortNumber'); ?>
            <small><?= \Yii::t('app', 'running exam{n,plural,=0{s} =1{} other{s}}', [
                'n' => $running_exams
            ]); ?></small>
            </h1>
        </div>
    </div>

    <div class="col-md-6">
        <div class="well">
            <h1 class="text-center">
            <?= ActiveEventField::widget([
                'event' => 'newActivities',
                'content' => yii::$app->formatter->format($new_activities, 'shortNumber'),
                'jsonSelector' => 'newActivities',
                'jsHandler' => 'function(d, s){s.innerHTML = eval(s.innerHTML + d)}',
                'options' => [ 'tag' => 'span' ],
            ]); ?>
            <small><?= \Yii::t('app', 'new activit{n,plural,=0{ies} =1{y} other{ies}}', [
                'n' => $new_activities
            ]); ?></small>
            </h1>
        </div>
    </div>

    <div class="col-md-6">
        <div class="well">
            <h1 class="text-center">
            <?= yii::$app->formatter->format($total_exams, 'shortNumber'); ?>
            <small><?= \Yii::t('app', 'exam{n,plural,=0{s} =1{} other{s}} created', [
                'n' => $total_exams
            ]); ?></small>
            </h1>
        </div>
    </div>

    <div class="col-md-6">
        <div class="well">
            <h1 class="text-center">
            <?= yii::$app->formatter->format($completed_exams, 'shortNumber'); ?>
            <small><?= \Yii::t('app', 'exam{n,plural,=0{s} =1{} other{s}} completed', [
                'n' => $completed_exams
            ]); ?></small>
            </h1>
        </div>
    </div>

    <div class="col-md-6">
        <div class="well">
            <h1 class="text-center">
            <?= yii::$app->formatter->format($total_duration, 'hours'); ?>
            <small><?= \Yii::t('app', 'hour{n,plural,=0{s} =1{} other{s}} spent in exams', [
                'n' => $total_duration
            ]); ?></small>
            </h1>
        </div>
    </div>

    <div class="col-md-6">
        <div class="well">
            <h1 class="text-center">
            Ø 
            <small><?= yii::$app->formatter->format($average_duration, 'duration'); ?></small>
            </h1>
        </div>
    </div>

    <div class="col-md-12">
        <div class="jumbotron alert-info">
            <p><?= \Yii::t('app', 'Check your exam result!') ?></p>
            <?= $this->render('/result/_form', [
                'model' => new Ticket(),
            ]);
            ?>
        </div>
    </div>

</div>
