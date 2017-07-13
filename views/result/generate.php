<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Result */
/* @var $form yii\widgets\ActiveForm */

$this->title = 'Generate Result';
$this->params['breadcrumbs'][] = ['label' => 'Exams', 'url' => ['exam/index']];
$this->params['breadcrumbs'][] = ['label' => $model->exam->name, 'url' => [
    'exam/view',
    'id' => $model->exam->id,
]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ticket-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="result-form">

        <?php $form = ActiveForm::begin(); ?>
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'path')->textInput() ?>
                <?= $form->field($model, 'inc_dotfiles')->checkbox() ?>
                <?= $form->field($model, 'inc_screenshots')->checkbox() ?>                
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'inc_pattern')->dropDownList([
                    'word_documents' => 'Word Documents',
                    'images' => 'Images',
                ], [ 'multiple'=>'multiple']) ?>
            </div>            
        </div>

        <div class="form-group">
            <?= Html::submitButton('Generate ZIP-File', ['class' => 'btn btn-success']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>
