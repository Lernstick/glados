<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $model Result */
/* @var $dataProvider ArrayDataProvider */


$columns = [
    [
        'attribute' => 'directory',
        'value' => function ($ticket) use ($model) {
            return '<i class="glyphicon glyphicon-folder-open"></i>&nbsp;<samp>' . $model->dirs[$ticket->token] . '</samp>';
        },
        'format' => 'raw',
        'label' => \Yii::t('results', 'ZIP Directory Name')
    ],  
    [
        'attribute' => 'test_taker',
        'value' => function ($ticket) {
            $p = 'ticket/index/all';
            $permission = \Yii::$app->authManager->getPermission($p);

            if ($ticket->exam === null) {
                return null; // no exam/ticket exists
            } else if ($ticket->exam->user_id == \Yii::$app->user->id || Yii::$app->user->can($p)) {
                return  $ticket->test_taker; // permission to list is granted
            } else {
                // no permission
                return substitute('<span class="not-set">(<abbr title=\'{description}\'>{text}</abbr>)</span>', [
                    'text' => \Yii::t('app', 'not visible'),
                    'description' => \Yii::t('app', 'You are not allowed to view this property. You need to have the following permission: "{permission} ({short})".', [
                        'permission' => $permission === null ? $p : \Yii::t('permission',  $permission->description),
                        'short' => $p,
                    ])
                ]);
            }
        },
        'format' => 'raw',
    ], 
    [
        // the token exactly as it is given in the zip file
        'attribute' => 'token',
        'value' => function ($ticket) {
            if ($ticket->exam !== null) {
                return Html::a($ticket->token,  ['ticket/view', 'id' => $ticket->id], ['data-pjax' => 0]);
            } else {
                return $ticket->token;
            }
        },
        'format' => 'raw',
    ], 
    [
        'attribute' => 'examName',
        'value' => function ($ticket) {
            $p = 'ticket/index/all';
            $permission = \Yii::$app->authManager->getPermission($p);

            if ($ticket->exam === null) {
                return null; // no exam/ticket exists
            } else if ($ticket->exam->user_id == \Yii::$app->user->id || Yii::$app->user->can($p)) {
                // permission to list is granted
                return Html::a($ticket->exam->name,  ['exam/view', 'id' => $ticket->exam->id], ['data-pjax' => 0]);
            } else {
                // no permission
                return substitute('<span class="not-set">(<abbr title=\'{description}\'>{text}</abbr>)</span>', [
                    'text' => \Yii::t('app', 'not visible'),
                    'description' => \Yii::t('app', 'You are not allowed to view this property. You need to have the following permission: "{permission} ({short})".', [
                        'permission' => $permission === null ? $p : \Yii::t('permission',  $permission->description),
                        'short' => $p,
                    ])
                ]);
            }
        },
        'format' => 'raw',
    ],
];

if ($step == 2) {
    $columns[] = [
        'attribute' => 'notice',
        'value' => function ($ticket) {
            if ($ticket->exam !== null) {
                if ($ticket->exam->user_id == \Yii::$app->user->id || Yii::$app->user->can('result/submit/all')) {
                    return !empty($ticket->result) && file_exists($ticket->result) ? '<i class="glyphicon glyphicon-ok"></i> ' . \Yii::t('results', 'There is already a submitted result. The existing one will be overwritten!') : '<i class="glyphicon glyphicon-ok"></i> ' . \Yii::t('results', 'This Ticket has no submitted result yet.');
                } else {
                    $p = 'result/submit/all';
                    $permission = \Yii::$app->authManager->getPermission($p);
                    return '<i class="glyphicon glyphicon-alert"></i> ' . \Yii::t('results', 'No permission to submit results for this ticket. You need to have the following permission "{permission} ({short})".', [
                            'permission' => $permission === null ? $p : \Yii::t('permission',  $permission->description),
                            'short' => $p,
                    ]);
                }
            } else {
                return '<i class="glyphicon glyphicon-alert"></i> ' . \Yii::t('results', 'No ticket with this token found. Unable to submit anything.');
            }
        },
        'format' => 'raw',
        'label' => \Yii::t('results', 'Notice')
    ];
    $rowOptions = function($ticket) {
        if ($ticket->exam !== null) {
            if ($ticket->exam->user_id == \Yii::$app->user->id || Yii::$app->user->can('result/submit/all')) {
                return ['class' => !empty($ticket->result) && file_exists($ticket->result) ? 'alert alert-warning warning' : 'alert alert-success success'];
            }
        }
        return ['class' => 'alert alert-danger danger'];
    };
} else if ($step == 'done') {
    $columns[] = [
        'attribute' => 'result',
        'value' => function ($ticket) {
            if ($ticket->exam !== null) {
                if ($ticket->exam->user_id == \Yii::$app->user->id || Yii::$app->user->can('result/submit/all')) {
                    if (!empty($ticket->result) && file_exists($ticket->result)) {
                        return '<i class="glyphicon glyphicon-ok-sign"></i> ' . \Yii::t('results', 'Result successfully submitted.');
                    } else {
                        return '<i class="glyphicon glyphicon-alert"></i> ' . \Yii::t('results', 'Error submitting result. Please check your zip file.');
                    }
                } else {
                    $p = 'result/submit/all';
                    $permission = \Yii::$app->authManager->getPermission($p);
                    return '<i class="glyphicon glyphicon-alert"></i> ' . \Yii::t('results', 'No permission to submit results for this ticket. You need to have the following permission "{permission} ({short})".', [
                            'permission' => $permission === null ? $p : \Yii::t('permission',  $permission->description),
                            'short' => $p,
                    ]);
                }
            } else {
                return '<i class="glyphicon glyphicon-alert"></i> ' . \Yii::t('results', 'No ticket with this token found. Unable to submit anything.');
            }
        },
        'format' => 'raw',
        'label' => \Yii::t('results', 'Notice')
    ];
    $rowOptions = function($ticket) {
        if ($ticket->exam !== null) {
            if ($ticket->exam->user_id == \Yii::$app->user->id || Yii::$app->user->can('result/submit/all')) {
                return ['class' => !empty($ticket->result) && file_exists($ticket->result) ? 'alert alert-success success' : 'alert alert-danger danger'];
            }
        }
        return ['class' => 'alert alert-danger danger'];
    };
}

$columns[] = [
    'class' => 'yii\grid\ActionColumn',
    'template' => '{download}',
    'buttons' => [
        'download' => function ($url, $model, $key) {
            return !empty($model->result) && file_exists($model->result) ? Html::a('<span class="glyphicon glyphicon-save-file"></span>', $url,
                [
                    'title' => \Yii::t('ticket', 'Download Result'),
                    'data-pjax' => '0',
                    'enabled' => false,
                ]
            ) : null;
        },
    ],
    'urlCreator' => function ($action, $model, $key, $index) {
        return Url::toRoute(['result/' . $action, 'token' => $model->token]);
    },
];

?>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'tableOptions' => ['class' => 'table table-bordered table-hover'],
    'layout' => '{items} {summary} {pager}',
    'rowOptions' => $rowOptions,
    'columns' => $columns,
    'pager' => [
        'class' => app\widgets\CustomPager::className(),
        'selectedLayout' => Yii::t('app', '{selected} <span style="color: #737373;">items</span>'),
    ],
]); ?>