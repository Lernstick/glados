<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Exam */

$this->title = 'Create Exam';
$this->params['breadcrumbs'][] = ['label' => 'Exams', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="exam-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="alert alert-success" role="alert">
        <span class="glyphicon glyphicon-alert"></span>
        <span>For more information, please visit <?= Html::a('Manual / Create an exam', ['/howto/view', 'id' => 'create-exam.md'], ['class' => 'alert-link']) ?>.</span>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>