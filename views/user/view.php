<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model app\models\User */

$this->title = $model->username;
$this->params['breadcrumbs'][] = ['label' => \Yii::t('users', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$active_tabs = <<<JS
// Change hash for page-reload
$('.nav-tabs a').on('shown.bs.tab', function (e) {
    var prefix = "tab_";
    window.location.hash = e.target.hash.replace("#", "#" + prefix);
});

// Javascript to enable link to tab
$(window).bind('hashchange', function() {
    var prefix = "tab_";
    $('.nav-tabs a[href*="' + document.location.hash.replace(prefix, "") + '"]').tab('show');
}).trigger('hashchange');
JS;
$this->registerJs($active_tabs);

?>
<div class="user-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <ul class="nav nav-tabs">
        <li class="active"><a data-toggle="tab" href="#general">
            <i class="glyphicon glyphicon-home"></i>
            <?= \Yii::t('users', 'General') ?>
        </a></li>
        <li>
            <?= Html::a(
                '<i class="glyphicon glyphicon-check"></i> ' . \Yii::t('users', 'Permissions'),
                '#permissions',
                ['data-toggle' => 'tab']
            ) ?>
        </li>
        <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                <i class="glyphicon glyphicon-list-alt"></i>
                <?= \Yii::t('users', 'Actions') ?><span class="caret"></span>
            </a>
            <ul class="dropdown-menu">
                <li>
                    <?= Html::a(
                        '<span class="glyphicon glyphicon-pencil"></span> '. \Yii::t('users', 'Edit'),
                        ['update', 'id' => $model->id],
                        [
                            'class' => 'btn',
                            'style' => ['text-align' => 'left'],
                            'disabled' => $model->type != '0',
                        ],
                        ['data-pjax' => 0]
                    ) ?>
                </li>
                <li>
                    <?= Html::a(
                        '<span class="glyphicon glyphicon-wrench"></span> ' . \Yii::t('users', 'Reset Password'),
                        ['reset-password', 'id' => $model->id],
                        [
                            'class' => 'btn',
                            'style' => ['text-align' => 'left'],
                            'disabled' => $model->type != '0',
                        ],
                        ['data-pjax' => 0]
                    ) ?>
                </li>
                <li>
                    <?= Html::a(
                        '<span class="glyphicon glyphicon-trash"></span> ' . \Yii::t('users', 'Delete'),
                        ['delete', 'id' => $model->id],
                        [
                            'data' => [
                                'confirm' => \Yii::t('users', 'Are you sure you want to delete this item?'),
                                'method' => 'post',
                            ],
                        ]
                    ) ?>
                </li>
            </ul>            
        </li>

    </ul>

    <div class="tab-content">

        <div id="general" class="tab-pane fade in active">

            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'username',
                    'role',
                    [
                        'attribute' => 'authMethod.name',
                        'label' => Yii::t('auth', 'Authentication Method'),
                        'value' => Html::a($model->authMethod->name . ' (' . $model->authMethod->typeName . ')', ['auth/view', 'id' => $model->authMethod->id
                        ]),
                        'format' => 'raw',
                    ],
                    'last_visited',
                ],
            ]) ?>

            <?= $this->render('@app/views/_notification') ?>

        </div>

        <div id="permissions" class="tab-pane fade">

            <?php $_GET = array_merge($_GET, ['#' => 'tab_permissions']); ?>
            <?= GridView::widget([
                'dataProvider' => $permissionDataProvider,
                'tableOptions' => ['class' => 'table table-bordered table-hover'],
                'rowOptions' => function($data) {
                    return null;
                },

                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    'description',
                ],
                'layout' => '{items} {pager}',
                'emptyText' => \Yii::t('users', 'No permissions found.'),
            ]); ?>

        </div>

    </div>

</div>
