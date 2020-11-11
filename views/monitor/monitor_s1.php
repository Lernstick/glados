<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */
/* @var $form yii\widgets\ActiveForm */
/* @var $searchModel app\models\TicketSearch */

$this->title = \Yii::t('monitor', 'Monitoring - Choose Exam');
$this->params['breadcrumbs'][] = ['label' => \Yii::t('exams', 'Exams'), 'url' => ['exam/index']];
$this->params['breadcrumbs'][] = ['label' => \Yii::t('monitor', 'Monitoring'), 'url' => ['monitor/']];
$this->params['breadcrumbs'][] = \Yii::t('exams', 'Choose Exam');

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
        <span><?= \Yii::t('app', 'For more information, please visit {link}.', [
            'link' => Html::a('Manual / Monitoring exams', ['/howto/view', 'id' => 'monitoring-exams.md'], ['class' => 'alert-link', 'target' => '_new'])
        ]) ?></span>

    </div>

    <div class="result-form">

        <?= Html::beginForm(['monitor/view'], 'get', ['enctype' => 'multipart/form-data']); ?>
        <div class="row">
            <div class="col-md-12">

                <?= Select2::widget([
                    'name' => 'id',
                    'options' => ['placeholder' => \Yii::t('exams', 'Choose an Exam ...')],
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
                ]); ?>

            </div>
        </div>
        <br>
        <div class="form-group">
            <?= Html::submitButton(\Yii::t('app', 'Next step'), ['class' => 'btn btn-success']) ?>
        </div>

        <?= Html::endForm() ?>

    </div>

</div>
