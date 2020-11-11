<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\forms\ExamForm */

$this->title = \Yii::t('exams', 'Edit Exam: {exam}', [ 'exam' => $model->exam->name ]);
$this->params['breadcrumbs'][] = ['label' => \Yii::t('exams', 'Exams'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->exam->name, 'url' => ['view', 'id' => $model->exam->id]];
$this->params['breadcrumbs'][] = \Yii::t('exams', 'Edit');

?>
<div class="exam-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'step' => $step,
    ]) ?>

</div>
