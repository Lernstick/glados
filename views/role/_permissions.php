<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;

/* @var $this yii\web\View */
/* @var $dataProvider ArrayDataProvider Dataprovider of all permission in the system */
/* @var $permissions array[] list of permissions associated to the Role model */
/* @var $form yii\widgets\ActiveForm */

$form = isset($form) ? $form : null;

$js = <<< 'SCRIPT'
/* Checkboxes change classes of tr elements */
$('.js_checkbox').change(function () {
    if (this.checked) {
        $(this).closest('tr').addClass('text-bold');
        $(this).closest('tr').removeClass('text-muted');
    } else {
        $(this).closest('tr').addClass('text-muted');
        $(this).closest('tr').removeClass('text-bold');
    }
});
SCRIPT;
$this->registerJs($js, \yii\web\View::POS_READY);

?>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'tableOptions' => ['class' => 'table table-bordered table-hover'],
    'rowOptions' => function($model, $key, $index, $column) use ($permissions, $form) {
        if (isset($form)){
            return [
                'class' => in_array($model->name, $permissions) ? 'text-bold' : 'text-muted',
                //'onclick' => 'this.getElementsByTagName("input")[0].checked = ! this.getElementsByTagName("input")[0].checked;',
                //'style' => 'cursor: pointer'
            ];
        } else {
            return [
                'class' => in_array($model->name, $permissions) ? 'text-bold' : 'text-muted',
            ];
        }
    },

    'columns' => [
        [
            'class' => 'yii\grid\CheckboxColumn',
            'header' => isset($form) ? null : '',
            'name' => 'Role[children]',
            'checkboxOptions' => function ($model, $key, $index, $column) use ($permissions, $form) {
                return [
                    'disabled' => !isset($form),
                    'checked' => in_array($model->name, $permissions),
                    'class' => 'js_checkbox',
                ];
            }
        ],
        [
            'attribute' => 'name',
            'label' => \Yii::t('user', 'Name'),
            'value' => function ($model) {
                return Html::a($model->name, ['role/view', 'id' => $model->name]);
            },
            'format' => 'raw',
        ],
        [
            'attribute' => 'description',
            'label' => \Yii::t('user', 'Description'),
            'value' => function ($model) {
                return Yii::t('permission', $model->description);
            },
        ],
    ],
    'layout' => isset($form) ? '{items}' : '{items} {summary} {pager}',
    'emptyText' => \Yii::t('users', 'No permissions found.'),
    'pager' => [
        'class' => app\widgets\CustomPager::className(),
        'selectedLayout' => Yii::t('app', '{selected} <span style="color: #737373;">items</span>'),
    ],
]); ?>
