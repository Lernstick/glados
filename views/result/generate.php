<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $model app\models\Result */
/* @var $tickets array */
/* @var $form yii\widgets\ActiveForm */

$this->title = 'Generate Result';
$this->params['breadcrumbs'][] = ['label' => 'Exams', 'url' => ['exam/index']];
$this->params['breadcrumbs'][] = ['label' => $model->exam->name, 'url' => [
    'exam/view',
    'id' => $model->exam->id,
]];
$this->params['breadcrumbs'][] = $this->title;

$format_ft = <<< SCRIPT
function format_ft(state) {
    var map = {
        word: "*.doc, *.docx, *.rtf, *.odt",
        excel: "*.xl*, *.ods",
        pp: "*.ppt[m,s], *.pps[x,m], *.pot[x,m], *.odp",
        text: "*.txt, *.cfg, *.conf, *.ini",
        images: "*.jpg, *.jpeg, *.png, *.gif",
        pdf: "*.pdf",
    };

    if (!state.id) return state.text;
    if (typeof(map[state.id]) === 'undefined') return state.text;
    return state.text + ' (' + map[state.id] + ')';
}
SCRIPT;

$this->registerJs($format_ft, yii\web\View::POS_HEAD);

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
                <!--<?= $form->field($model, 'inc_pattern')->dropDownList([
                    'word_documents' => 'Word Documents',
                    'images' => 'Images',
                ], [ 'multiple'=>'multiple']) ?>-->
                <?= $form->field($model, 'inc_pattern')->widget(Select2::classname(), [
                    'data' => [
                        'word' => 'Word Documents',
                        'excel' => 'Excel Documents',
                        'pp' => 'Powerpoint Documents',
                        'pdf' => 'PDF Documents',
                        'text' => 'Text Files',
                        'images' => 'Images',
                    ],
                    'options' => [
                        'placeholder' => 'Select file types to include ...',
                        'multiple' => true,
                    ],
                    'pluginOptions' => [
                        'templateResult' => new JsExpression('format_ft'),                    
                        'allowClear' => true
                    ],
                ]); ?>                
            </div>            
        </div>

        <div class="row">
            <div class="col-md-12">

                <!--<?= $form->field($model, 'inc_ids')->dropDownList($tickets, [ 'multiple'=>'multiple']) ?>-->
                <?= $form->field($model, 'inc_ids')->widget(Select2::classname(), [
                    'data' => $tickets,
                    'options' => [
                        'value' => array_keys($tickets),
                        'placeholder' => 'Select Tickets ...',
                        'multiple' => true,
                    ],
                    'pluginOptions' => [
                        'allowClear' => true
                    ],
                ]); ?>
            </div>
        </div>

        <div class="form-group">
            <?= Html::submitButton('Generate ZIP-File', ['class' => 'btn btn-success']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>

</div>
