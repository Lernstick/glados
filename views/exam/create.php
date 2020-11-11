<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\ExamForm */

$this->title = \Yii::t('exams', 'Create Exam - Step 1');
$this->params['breadcrumbs'][] = ['label' => \Yii::t('exams', 'Exams'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="exam-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="alert alert-success" role="alert">
        <span class="glyphicon glyphicon-alert"></span>
        <span><?= \Yii::t('exams', 'For more information, please visit {link}.', [
            'link' => Html::a(\Yii::t('exams', 'Manual / Create an exam'), ['/howto/view', 'id' => 'create-exam.md'], ['class' => 'alert-link', 'target' => '_new'])
        ]) ?></span>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
        'step' => $step,
    ]) ?>

</div>