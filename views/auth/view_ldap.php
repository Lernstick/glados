<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\Pjax;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/* @var $this yii\web\View */
/* @var $model app\models\Auth */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \Yii::t('auth', 'Authentication Methods'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$options = [];
foreach ($model->ldap_options_name_map as $key => $value) {
    if (array_key_exists($key, $model->ldap_options)) {
        $options[$value] = is_array($model->ldap_options[$key])
            ? json_encode($model->ldap_options[$key])
            : $model->ldap_options[$key];
    }
}

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
<div class="auth-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <ul class="nav nav-tabs">
        <li class="active"><a data-toggle="tab" href="#general">
            <i class="glyphicon glyphicon-home"></i>
            <?= \Yii::t('auth', 'General') ?>
        </a></li>
        <li><a data-toggle="tab" href="#details">
            <i class="glyphicon glyphicon-th-list"></i>
            <?= \Yii::t('auth', 'Details') ?>
        </a></li>
        <li><a data-toggle="tab" href="#raw">
            <i class="glyphicon glyphicon-fire"></i>
            <?= \Yii::t('auth', 'Raw Config') ?>
        </a></li>
        <li class="dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                <i class="glyphicon glyphicon-list-alt"></i>
                <?= \Yii::t('auth', 'Actions') ?><span class="caret"></span>
            </a>
            <ul class="dropdown-menu">
                <li>
                    <?= Html::a(
                        '<span class="glyphicon glyphicon-pencil"></span> '. \Yii::t('auth', 'Edit'),
                        ['update', 'id' => $model->id],
                        ['data-pjax' => 0]
                    ) ?>
                </li>
                <li>
                    <?= Html::a(
                        '<span class="glyphicon glyphicon-trash"></span> ' . \Yii::t('auth', 'Delete'),
                        ['delete', 'id' => $model->id],
                        [
                            'data' => [
                                'confirm' => \Yii::t('auth', 'Are you sure you want to delete this item?'),
                                'method' => 'post',
                            ],
                        ]
                    ) ?>
                </li>
                <li>
                    <?= Html::a(
                        '<span class="glyphicon glyphicon-user"></span> '. \Yii::t('auth', 'Test Login'),
                        ['test', 'id' => $model->id],
                        ['data-pjax' => 0]
                    ) ?>
                </li>
                <li>
                    <?= Html::a(
                        '<span class="glyphicon glyphicon-retweet"></span> '. \Yii::t('auth', 'Migrate Local Users'),
                        ['migrate', 'id' => $model->id],
                        ['data-pjax' => 0]
                    ) ?>
                </li>
            </ul>            
        </li>

    </ul>

    <p></p>

    <div class="tab-content">

        <?php Pjax::begin([
            'id' => 'general',
            'options' => ['class' => 'tab-pane fade in active'],
        ]); ?>

            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'order',
                    'typeName',
                    //'config',
                    'name',
                    'description',
                    'domain',
                    [
                        'attribute' => 'mapping',
                        'format' => [
                            'mapping',
                            [
                                \Yii::t('auth', 'LDAP Group'),
                                \Yii::t('auth', 'Role')
                            ]
                        ],
                    ],
                ],
            ]) ?>

        <?php Pjax::end(); ?>

        <?php Pjax::begin([
            'id' => 'details',
            'options' => ['class' => 'tab-pane fade'],
        ]); ?>

            <?php $_GET = array_merge($_GET, ['#' => 'tab_details']); ?>

            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'base',
                    'ldap_uri',
                    'ldap_port',
                    'ldap_scheme',
                    [
                        'attribute' => 'ldap_options',
                        'format' => [
                            'mapping',
                            [
                                \Yii::t('auth', 'LDAP Option'),
                                \Yii::t('auth', 'Value')
                            ]
                        ],
                        'value' => $options,
                    ],
                    'loginScheme',
                    'bindScheme',
                    'searchFilter',
                    'uniqueIdentifier',
                    'groupIdentifier',
                ],
            ]) ?>

        <?php Pjax::end(); ?>

        <?php Pjax::begin([
            'id' => 'raw',
            'options' => ['class' => 'tab-pane fade'],
        ]); ?>

            <?php $_GET = array_merge($_GET, ['#' => 'tab_raw']); ?>

            <pre><?= var_export($model->fileConfig[$model->id], true) ?></pre>

        <?php Pjax::end(); ?>

    </div>

</div>