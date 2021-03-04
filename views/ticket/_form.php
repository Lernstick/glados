<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\datetime\DateTimePicker;
use kartik\select2\Select2;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */
/* @var $form yii\widgets\ActiveForm */
/* @var $searchModel app\models\TicketSearch */

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
$this->registerJs($js, \yii\web\View::POS_READY);

if (Yii::$app->request->isAjax) {
    $ajax = <<< 'SCRIPT'
    $("form#ajax-form input:text, form textarea").first().select();

    $("form#ajax-form").on('beforeSubmit', function(event) {
        event.preventDefault(); // stopping submitting
        var data = $(this).serializeArray();
        var url = $(this).attr('action');
        var container = $(this).closest('[data-pjax-container]')[0];

        $.ajax({
            url: url,
            type: 'post',
            dataType: 'json',
            data: data,
            container: container
        }).done(function(response) {
            if (response == true) {
                $.pjax.reload({container: "#" + container.id, async:false});
            }
        }).fail(function() {
            alert("Internal Server Error");
        });

        return false;

    });
SCRIPT;
    // Register ajax submit javascript
    $this->registerJs($ajax, \yii\web\View::POS_READY);
}

?>

<div class="ticket-form">

    <?php $form = ActiveForm::begin([
        'id' => 'ajax-form',
        'enableClientValidation' => true,
        'enableAjaxValidation' => Yii::$app->request->isAjax,
        'validationUrl' => [ 'ticket/update', 'id' => $model->id, 'mode' => 'editable', 'attr' => $attr, 'validate' => true ],
        'fieldConfig' => Yii::$app->request->isAjax ? [
            'labelOptions' => [ 'class' =>  'hidden' ],
            'hintOptions' => [ 'class' => 'hidden' ],
        ] : null
    ]); ?>

    <?php

    //fields
    $token = $form->field($model, 'token')->textInput(['readOnly' => false]);
    $exam = $form->field($model, 'exam_id')->widget(Select2::classname(), [
        'data' => $searchModel->getExamList($model->exam_id),
        'pluginOptions' => [
            'dropdownAutoWidth' => true,
            'width' => 'auto',
            'allowClear' => true,
            'placeholder' => '',
            'ajax' => [
                'url' => \yii\helpers\Url::to(['exam/index', 'mode' => 'list', 'attr' => 'resultExam']),
                'dataType' => 'json',
                'delay' => 250,
                'cache' => true,
                'data' => new JsExpression('function(params) {
                    return {
                        q: params.term,
                        page: params.page,
                        per_page: 10
                    };
                }'),
                'processResults' => new JsExpression('function(data, page) {
                    return {
                        results: data.results,
                        pagination: {
                            more: data.results.length === 10 // If there are 10 matches, theres at least another page
                        }
                    };
                }'),
            ],
            'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
            'templateResult' => new JsExpression('function(q) { return q.text; }'),
            'templateSelection' => new JsExpression('function (q) { return q.text; }'),
        ],
        'options' => [
            'placeholder' => \Yii::t('ticket', 'Choose an Exam ...')
        ]
    ]);
    $start = $model->isNewRecord ? null : $form->field($model, 'start')->widget(DateTimePicker::classname(), [
        'options' => ['placeholder' => \Yii::t('ticket', 'Enter start time ...')],
        'pluginOptions' => [
           'format' => 'yyyy-mm-dd hh:ii:ss',
           'todayHighlight' => true,
           'todayBtn' => true,
           'autoclose' => true,
        ]
    ]);
    $end = $model->isNewRecord ? null : $form->field($model, 'end')->widget(DateTimePicker::classname(), [
        'options' => ['placeholder' => \Yii::t('ticket', 'Enter end time ...')],
        'pluginOptions' => [
            'format' => 'yyyy-mm-dd hh:ii:ss',
            'todayHighlight' => true,
            'todayBtn' => true,
            'autoclose' => true,
        ]
    ]);
    $backup_interval = $form->field($model, 'backup_interval', [
        'template' => '{label}<div class="input-group">{input}<span class="input-group-addon" id="basic-addon2">' . \Yii::t('ticket', 'seconds') . '</span></div>{hint}{error}'
    ])->textInput(['type' => 'number']);
    $time_limit = $form->field($model, 'time_limit', [
        'template' => '{label}<div class="input-group">{input}<span class="input-group-addon" id="basic-addon2">' . \Yii::t('ticket', 'minutes') . '</span></div>{hint}{error}'
    ])->textInput(['type' => 'number']);
    $test_taker = $form->field($model, 'test_taker')->textInput();

    ?>

    <?php if ($attr == null) { ?>

        <div class="row">
            <div class="col-md-6"><?= $token ?></div>
            <div class="col-md-6"><?= $exam ?></div>
        </div>

        <div class="row">
                <div class="col-md-6"><?= $start ?></div>
                <div class="col-md-6"><?= $end ?></div>
        </div>

        <div class="row">
            <div class="col-md-6"><?= $backup_interval ?></div>
            <div class="col-md-6"><?= $time_limit ?></div>
        </div>

        <div class="row">
            <div class="col-md-6"><?= $test_taker ?></div>
        </div>

        <?= $attr == null && YII_ENV_DEV ? $this->render('_form_dev', [
            'model' => $model,
            'form' => $form,
        ]) : null; ?>

    <?php } else {
        if ($attr == 'token') {
            echo $token;
        } else if ($attr == 'exam') {
            echo $exam;
        } else if ($attr == 'start') {
            echo $start;
        } else if ($attr == 'end') {
            echo $end;
        } else if ($attr == 'backup_interval') {
            echo $backup_interval;
        } else if ($attr == 'time_limit') {
            echo $time_limit;
        } else if ($attr == 'test_taker') {
            echo $test_taker;
        }
    } ?>



    <?php if ($attr == null) { ?>
    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? \Yii::t('ticket', 'Create') : \Yii::t('ticket', 'Apply'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>
    <?php } else { ?>
        <?= Html::submitButton('<span class="glyphicon glyphicon-ok"></span>', ['class' => 'btn btn-primary btn-xs']) ?>
        <a href="" class="btn btn-danger btn-xs" role="button"><span class="glyphicon glyphicon-remove"></span></a>
    <?php } ?>

    <?php ActiveForm::end(); ?>

</div>
