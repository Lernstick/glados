<?php

use yii\widgets\ListView;
use yii\widgets\Pjax;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $searchModel app\models\HistorySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $model yii\base\Model the data model of the Item (for example: app\models\Ticket, app\models\Exam, app\models\User, ...) */

?>

<?php Pjax::begin() ?>

<?php $x = ListView::begin([
    'dataProvider' => $dataProvider,
    'options' => [
        'tag' => 'ul',
        'class' => 'list-unstyled timeline widget',
    ],
    'itemView' => '_item',
    'itemOptions' => ['tag' => 'li'],
    'viewParams' => [
        'itemModel' => $model,
        'searchModel' => $searchModel,
    ],
    'summaryOptions' => [
        'class' => 'summary col-xs-12 col-md-12',
    ],
    'emptyText' => \Yii::t('ticket', 'No history items found.'),
    'layout' => '{items}',
    'pager' => [
        'class' => app\widgets\CustomPager::className(),
        'selectedLayout' => Yii::t('app', '{selected} <span style="color: #737373;">items</span>'),
    ],
]); ?>

<?php $form = ActiveForm::begin([
    'method' => 'get',
    'id' => 'history-form',
    'action' => ['', 'id' => $model->id, '#' => 'history'],
]); ?>

<div class="row">
    <div class="col-md-7">
        <?= $x->renderSummary(); ?>
        <?= $x->renderPager(); ?>
    </div>
    <div class="col-md-5">
        <?= $form->field($searchModel, 'column')->dropDownList($searchModel->getColumnList($model), [
            'prompt' => \Yii::t('history', 'Choose a column to filter ...'),
            'onchange' => 'this.form.submit()',
        ])->label(false); ?>
    </div>
</div>

<hr>

<?php ActiveForm::end(); ?>

<?php ListView::end(); ?>

<?php Pjax::end() ?>
