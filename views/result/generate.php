<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $model app\models\Result */
/* @var $tickets array */
/* @var $form yii\widgets\ActiveForm */

$this->title = 'Generate Result - ' . $model->exam->name;
$this->params['breadcrumbs'][] = ['label' => 'Exams', 'url' => ['exam/index']];
$this->params['breadcrumbs'][] = ['label' => 'Generate Result', 'url' => ['result/generate']];
$this->params['breadcrumbs'][] = ['label' => $model->exam->name, 'url' => [
    'exam/view',
    'id' => $model->exam->id,
]];

$js = <<< 'SCRIPT'
/* To initialize BS3 popovers set this below */
$(function () { 
    $("[data-toggle='popover']").popover(); 
});

$('.hint-block').each(function () {
    var $hint = $(this);

    $hint.parent().find('label').after('&nbsp<a tabindex="0" role="button" class="hint glyphicon glyphicon-question-sign"></a>');

    $hint.parent().find('a.hint').popover({
        html: true,
        trigger: 'focus',
        placement: 'right',
        //title:  $hint.parent().find('label').html(),
        title:  'Description',
        toggle: 'popover',
        container: 'body',
        content: $hint.html()
    });

    $hint.remove()
});
SCRIPT;
// Register tooltip/popover initialization javascript
$this->registerJs($js);

$format_pt = <<< SCRIPT
function format_pt(state) {
    var map = {
        word: "*.doc, *.docx, *.rtf, *.odt",
        excel: "*.xl*, *.ods",
        pp: "*.ppt[m,s], *.pps[x,m], *.pot[x,m], *.odp",
        text: "*.txt, *.cfg, *.conf, *.ini",
        images: "*.jp[e]g, *.png, *.gif",
        pdf: "*.pdf",
    };

    if (!state.id) return state.text;
    if (typeof(map[state.id]) === 'undefined') return state.text;
    return state.text + ' (' + map[state.id] + ')';
}
SCRIPT;

$format_tk = <<< SCRIPT
function format_tk(state) {
    a = state.text.split(' - ');
    return a[0] == '_NoName' ? a[1] : a[0];
}

function format_rtk(state) {
    a = state.text.split(' - ')
    return $('<div class="row" style="margin-left:0px; margin-right:0px;"><div class="col-md-4">' + a[0] + '</div><div class="col-md-4">' + a[1] + '</div><div class="col-md-4">' + a[2] + '</div></div>');
}
SCRIPT;

$this->registerJs($format_pt, yii\web\View::POS_HEAD);
$this->registerJs($format_tk, yii\web\View::POS_HEAD);

?>
<div class="ticket-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="result-form">

        <?php $form = ActiveForm::begin(); ?>
        <div class="row">
            <div class="col-md-12">
                <?= $form->field($ticket, 'exam_id')->dropDownList([
                    $ticket->exam_id => $ticket->exam->subject . ' - ' . $ticket->exam->name
                ], [
                    'prompt' => 'Choose an Exam ...',
                    'readOnly' => true,
                    'disabled' => true,
                ])->hint('Choose the exam to generate results from.') ?>
            </div>
        </div>
        <?php ActiveForm::end(); ?>

        <hr>

        <?php $form = ActiveForm::begin(); ?>

        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'path', [
                'template' => '{label}<div class="input-group"><span class="input-group-addon" id="basic-addon2">' . $ticket->exam->backup_path . '</span>{input}</div>{hint}{error}'
            ])->textInput() ?>
                <?= $form->field($model, 'inc_dotfiles')->checkbox() ?>
                <?= $form->field($model, 'inc_screenshots')->checkbox() ?>                
            </div>
            <div class="col-md-6">
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
                        'templateResult' => new JsExpression('format_pt'),
                        'allowClear' => true
                    ],
                ]); ?>                
            </div>            
        </div>

        <div class="row">
            <div class="col-md-12">
                <?= $form->field($model, 'inc_ids')->widget(Select2::classname(), [
                    'data' => $tickets,
                    'options' => [
                        'value' => array_keys($selectedTickets),
                        'placeholder' => 'Select Tickets ...',
                        'multiple' => true,
                    ],
                    'pluginOptions' => [
                        'templateResult' => new JsExpression('format_rtk'),
                        'templateSelection' => new JsExpression('format_tk'),                    
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
