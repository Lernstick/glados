<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model app\models\AuthMigrateForm */
/* @var $searchmodel app\models\AuthSearch */

$from = $model->fromModel;
$to = $model->toModel;

$this->title = \Yii::t('auth', 'Migrate users from {from} to {to}', [
    'from' => is_object($from) ? $from->name : $from,
    'to' => $to->name,
]);

$this->params['breadcrumbs'][] = ['label' => \Yii::t('auth', 'Authentication Methods'), 'url' => ['index']];
$this->params['breadcrumbs'][] = \Yii::t('auth', 'Migrate');
$this->params['breadcrumbs'][] = \Yii::t('auth', 'Summary');

?>
<div class="auth-mirgate">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="media-body">
        <span><?= \Yii::t('auth', 'The users are migrated! The list further down gives an overview of the migrated users.') ?></span>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => ['class' => 'table table-bordered table-hover'],
        'layout' => '{items} {summary} {pager}',
        'emptyText' => \Yii::t('auth', 'No users found for migration.'),
        'rowOptions' => function($model) use ($to) {
            return ['class' => $model->type === $to->id ? 'success' : 'danger'];
        },
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'username',
            [
                'attribute' => 'type',
                'label' => Yii::t('auth', 'Authentication Method'),
                'value' => function($model) {
                    if ($model->authMethod !== null) {
                        return $model->authMethod->name . ' (' . $model->authMethod->typeName . ')';
                    } else {
                        return Yii::t('auth', "No Authentication Method");
                    }
                },
                'format' => 'raw',
            ],
            'identifier',
            [
                'attribute' => 'result',
                'value' => function ($model) use ($to) {
                    return $model->type === $to->id ? '<i class="glyphicon glyphicon-ok-sign"></i> ' . \Yii::t('auth', 'Migration of user successful.') : '<i class="glyphicon glyphicon-alert"></i> ' . \Yii::t('auth', 'Error migrating user.');
                },
                'format' => 'raw',
                'label' => \Yii::t('auth', 'Notice')
            ],            
        ],
    ]); ?>
</div>
