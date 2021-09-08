<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\ListView;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\data\ArrayDataProvider;

/* @var $this yii\web\View */
/* @var $model app\models\Permission */
/* @var $dataProvider ArrayDataProvider */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \Yii::t('user', 'Roles'), 'url' => ['index']];
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
<div class="role-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <ul class="nav nav-tabs">
        <li class="active"><a data-toggle="tab" href="#general">
            <i class="glyphicon glyphicon-home"></i>
            <?= \Yii::t('users', 'General') ?>
        </a></li>
        <li>
            <?= Html::a(
                '<i class="glyphicon glyphicon-check"></i> ' . \Yii::t('users', 'Interhited Permissions'),
                '#permissions',
                ['data-toggle' => 'tab']
            ) ?>
        </li>
    </ul>

    <div class="tab-content">

        <div id="general" class="tab-pane fade in active">

            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'created_at:timeago',
                    'name',
                    'description',
                    'updated_at:timeago',
                ],
            ]) ?>

        </div>

        <div id="permissions" class="tab-pane fade">

            <?php $_GET = array_merge($_GET, ['#' => 'tab_permissions']); ?>

            <?= $this->render('_permissions', [
                'dataProvider' => $dataProvider,
                'permissions' => array_column(Yii::$app->authManager->getPermissionsByRole($model->name), 'name'),
            ]) ?>

        </div>

    </div>

</div>
