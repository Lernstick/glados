<?php

use app\components\ActiveEventField;

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
            <?= yii::$app->formatter->format($completed_exams, 'shortNumber'); ?> / <?= yii::$app->formatter->format($total_tickets, 'shortNumber'); ?>
            <small><?= \Yii::t('app', 'exam{n,plural,=0{s} =1{} other{s}} completed', [
                'n' => $total_tickets
            ]); ?></small>
            </h1>
        </div>
    </div>

    <div class="col-md-6">
        <div class="well">
            <h1 class="text-center">
            <?= yii::$app->formatter->format($total_duation, 'hours'); ?>
            <small><?= \Yii::t('app', 'hour{n,plural,=0{s} =1{} other{s}} spent in exams', [
                'n' => $total_duation
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

    <div class="jumbotron" style="background-color: transparent;">
        <p><a class="btn btn-lg btn-success" href="index.php?r=exam/index">Show my Exams &raquo;</a></p>
    </div>

</div>
