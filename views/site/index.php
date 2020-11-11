<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Ticket;

/* @var $this yii\web\View */

$this->title = 'GLaDOS';
?>
<div class="site-index">

    <?php if (!Yii::$app->user->isGuest) { ?>
        <div class="col-md-12">
            <div class="jumbotron alert-info">
                <h2><?= \Yii::t('app', 'Welcome!'); ?></h2>
                <p><?= \Yii::t('app', 'Try out the new live overview to monitor running exams.'); ?></p>
                <p>
                    <?= Html::a(\Yii::t('main', 'Monitor Exams'), Url::to(['/monitor']), ['class' => 'btn btn-primary btn-lg', 'role' => 'button']) ?>
                    <?= Html::a(\Yii::t('app', 'Learn more'), Url::to(['/howto/monitoring-exams.md']), ['class' => 'btn btn-default btn-lg', 'role' => 'button']) ?>
                </p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="well">
                <h1 class="text-center">
                <?= \Yii::t('app', '<small>You have </small>{n_formatted}<small> exam{n,plural,=0{s} =1{} other{s}} created</small>', [
                    'n' => $total_exams,
                    'n_formatted' => yii::$app->formatter->format($total_exams, 'shortNumber'),
                ]); ?>
                </h1>
            </div>
        </div>

        <div class="col-md-6">
            <div class="well">
                <h1 class="text-center">
                <?= \Yii::t('app', '<small>You have </small>{n_formatted}<small> new activit{n,plural,=0{ies} =1{y} other{ies}}</small>', [
                    'n' => $new_activities,
                    'n_formatted' => yii::$app->formatter->format($new_activities, 'shortNumber'),
                ]); ?>
                </h1>
            </div>
        </div>

    <?php } ?>

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

    <?php if (Yii::$app->user->isGuest) { ?>
        <div class="col-md-12">
            <div class="jumbotron alert-info">
                <p><?= \Yii::t('app', 'Check your exam result!') ?></p>
                <?= $this->render('/result/_form', [
                    'model' => new Ticket(),
                ]);
                ?>
            </div>
        </div>
    <?php } ?>

</div>
