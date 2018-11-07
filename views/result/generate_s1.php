<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */
/* @var $form yii\widgets\ActiveForm */
/* @var $searchModel app\models\TicketSearch */

$this->title = 'Generate Result - Choose Exam';
$this->params['breadcrumbs'][] = ['label' => 'Exams', 'url' => ['exam/index']];
$this->params['breadcrumbs'][] = ['label' => 'Generate Result', 'url' => ['result/generate']];
$this->params['breadcrumbs'][] = 'Choose Exam';

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

?>
<div class="ticket-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="alert alert-success" role="alert">
        <span class="glyphicon glyphicon-alert"></span>
        <span>For more information, please visit <?= Html::a('Manual / Generate results', ['/howto/view', 'id' => 'generate-results.md'], ['class' => 'alert-link']) ?>.</span>
    </div>

    <div class="result-form">

        <?= Html::beginForm(['result/generate'], 'get', ['enctype' => 'multipart/form-data']); ?>
        <div class="row">
            <div class="col-md-12">

                <?= Select2::widget([
                    'name' => 'exam_id',
                    'options' => ['placeholder' => 'Choose an Exam ...'],
                    'pluginOptions' => [
                        'dropdownAutoWidth' => true,
                        'width' => 'auto',
                        'allowClear' => true,
                        'minimumInputLength' => 2,
                        'placeholder' => '',
                        'language' => [
                            'errorLoading' => new JsExpression("function () { return 'Waiting for results...'; }"),
                        ],
                        'ajax' => [
                            'url' => \yii\helpers\Url::to(['exam/index', 'mode' => 'list', 'attr' => 'resultExam']),
                            'dataType' => 'json',
                            'data' => new JsExpression('function(params) { return {q:params.term}; }')
                        ],
                        'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                        'templateResult' => new JsExpression('function(q) { return q.text; }'),
                        'templateSelection' => new JsExpression('function (q) { return q.text; }'),
                    ],
                ]); ?>

            </div>
        </div>
        <br>
        <div class="form-group">
            <?= Html::submitButton('Next step', ['class' => 'btn btn-success']) ?>
        </div>

        <?= Html::endForm() ?>

    </div>

</div>
