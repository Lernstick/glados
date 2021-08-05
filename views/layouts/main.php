<?php

/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;
use app\models\Activity;
use app\models\DaemonSearch;
use app\components\ActiveEventField;

AppAsset::register($this);

$activity = new Activity();
$newActivities = $activity->newActivities();

$daemons = new DaemonSearch();
$runningDaemons = $daemons->search([])->totalCount;

/* register the global YII_ENV variables */
$this->registerJs('var YII_ENV_DEV = ' . (YII_ENV_DEV ? 'true' : 'false') . ';', \yii\web\View::POS_HEAD);
$this->registerJs('var YII_DEBUG = ' . (YII_DEBUG ? 'true' : 'false') . ';', \yii\web\View::POS_HEAD);

$this->registerJs('jQuery.timeago.settings.cutoff = 1000*60*60*24;', \yii\web\View::POS_END);

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>

    <?= ActiveEventField::widget([
        'options' => [ 'tag' => 'title' ],
        'event' => 'newActivities',
        'content' => ( $newActivities ? '(' . $newActivities . ') ' : null ) . Html::encode($this->title),
        'jsonSelector' => 'newActivities',
        'jsHandler' => 'function(d){
            var r = /^\(([0-9]+)\)/;
            var v = r.exec(document.title);
            if(v == null){
                var l = 0;
            }else{
                var l = v[1];
            }
            document.title = document.title.replace(/^(\([0-9]+\))? ?/, "(" + eval(l + d) + ") ");
        }'
    ]); ?>

    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="wrap">
    <?php
    NavBar::begin([
        'brandLabel' => 'GLaDOS',
        'brandUrl' => Yii::$app->homeUrl,
        'options' => [
            'class' => 'navbar-inverse navbar-fixed-top',
        ],
    ]);
    if (YII_ENV_DEV) {
        echo "<p class='navbar-text' style='color:red; font-size:7px; margin:10px;'>YII_ENV_DEV=true<br>YII_DEBUG=" . (YII_DEBUG ? 'true' : 'false') . "<br>LANG=" . \Yii::$app->language . "</p>";
    }
    echo Nav::widget([
        'options' => ['class' => 'navbar-nav navbar-right'],
        'encodeLabels' => false,
        'items' => [
            [
                'label' => \Yii::t('main', 'Home'),
                'url' => ['/site/index']
            ],
            [
                'label' => \Yii::t('main', 'Actions'),
                'visible' => !Yii::$app->user->isGuest,
                'items' => [
                    [
                        'label' => '<i class="glyphicon glyphicon-user"></i> ' . \Yii::t('main', 'Create User'),
                        'url' => ['/user/create'],
                        'visible' => Yii::$app->user->can('user/create'),
                    ],
                    [
                        'label' => '<i class="glyphicon glyphicon-education"></i> ' . \Yii::t('main', 'Create Exam'),
                        'url' => ['/exam/create'],
                        'visible' => Yii::$app->user->can('exam/create'),
                    ],
                    [
                        'label' => '<i class="glyphicon glyphicon-file"></i> ' . \Yii::t('main', 'Create single Ticket'),
                        'url' => ['/ticket/create', 'mode' => 'single'],
                        'visible' => Yii::$app->user->can('ticket/create'),
                    ],
                    [
                        'label' => '<i class="glyphicon glyphicon-duplicate"></i> ' . \Yii::t('main', 'Create multiple Tickets'),
                        'url' => ['/ticket/create', 'mode' => 'many', 'type' => 'assigned'],
                        'visible' => Yii::$app->user->can('ticket/create'),
                    ],
                    [
                        'label' => '<i class="glyphicon glyphicon-barcode"></i> ' . \Yii::t('main', 'Submit Ticket'),
                        'url' => ['/ticket/update', 'mode' => 'submit'],
                        'visible' => Yii::$app->user->can('ticket/update'),
                    ],
                    [
                        'label' => '<i class="glyphicon glyphicon-eye-open"></i> ' . \Yii::t('main', 'Monitor Exams'),
                        'url' => ['/monitor'],
                        'visible' => Yii::$app->user->can('exam/view'),
                    ],
                    [
                        'label' => '<i class="glyphicon glyphicon-cloud-download"></i> ' . \Yii::t('main', 'Generate results'),
                        'url' => ['/result/generate'],
                        'visible' => Yii::$app->user->can('result/generate'),
                    ],
                    [
                        'label' => '<i class="glyphicon glyphicon-cloud-upload"></i> ' . \Yii::t('main', 'Submit results'),
                        'url' => ['/result/submit'],
                        'visible' => Yii::$app->user->can('result/submit'),
                    ],
                ],
            ],
            [
                'label' => \Yii::t('main', 'Activities ') . 
                    ActiveEventField::widget([
                        'content' => $newActivities,
                        'options' => [
                            'class' => 'badge',
                        ],
                        'event' => 'newActivities',
                        'jsonSelector' => 'newActivities',
                        'jsHandler' => 'function(d, s){
                            s.innerHTML = eval(s.innerHTML + d);
                            s.style.animation = "";
                            setTimeout(function (){s.style.animation = "bounce 1000ms linear both";},10);
                        }',
                    ]),
                'encode' => false,
                'url' => ['/activity/index'],
                'visible' => Yii::$app->user->can('activity/index'),
            ], 
            [
                'label' => \Yii::t('main', 'Exams'),
                'url' => ['/exam/index'],
                'visible' => Yii::$app->user->can('exam/index'),
            ],
            [
                'label' => \Yii::t('main', 'Tickets'),
                'url' => ['/ticket/index'],
                'visible' => Yii::$app->user->can('ticket/index'),
            ],
            [
                'label' => \Yii::t('main', 'System'),
                'visible' => !Yii::$app->user->isGuest,
                'items' => [

                    [
                        'label' => '<i class="glyphicon glyphicon-tasks"></i> ' . 
                            \Yii::t('main', 'Daemons ') . 
                            ActiveEventField::widget([
                                'content' => $runningDaemons,
                                'options' => [
                                    'class' => 'badge',
                                    'change-animation' => 'bounce 1000ms linear both',
                                ],
                                'event' => 'runningDaemons',
                                'jsonSelector' => 'runningDaemons',
                            ]),
                        'url' => ['/daemon/index'],
                        'visible' => Yii::$app->user->can('daemon/index'),
                    ],
                    [
                        'label' => '<i class="glyphicon glyphicon-user"></i> ' . \Yii::t('main', 'Users'),
                        'url' => ['/user/index'],
                        'visible' => Yii::$app->user->can('user/index'),
                    ],
                    [
                        'label' => '<i class="glyphicon glyphicon-tags"></i> ' . \Yii::t('main', 'Roles'),
                        'url' => ['/role/index'],
                        'visible' => Yii::$app->user->can('role/index'),
                    ],
                    [
                        'label' => '<i class="glyphicon glyphicon-tag"></i> ' . \Yii::t('main', 'Profile'),
                        'url' => ['/user/view', 'id' => Yii::$app->user->id],
                        'visible' => !Yii::$app->user->isGuest && Yii::$app->user->can('user/view'),
                    ],
                    [
                        'label' => '<i class="glyphicon glyphicon-dashboard"></i> ' . \Yii::t('main', 'Server Status'),
                        'url' => ['/server/status'],
                        'visible' => Yii::$app->user->can('server/status'),
                    ],
                    [
                        'label' => '<i class="glyphicon glyphicon-th-list"></i> ' . \Yii::t('main', 'Server Config'),
                        'url' => ['/server/config'],
                        'visible' => Yii::$app->user->can('server/config'),
                    ],
                    [
                        'label' =>  '<i class="glyphicon glyphicon-cog"></i> ' . \Yii::t('main', 'Settings'),
                        'url' => ['/setting/index'],
                        'visible' => Yii::$app->user->can('setting/index'),
                    ], 
                    [
                        'label' => '<i class="glyphicon glyphicon-lock"></i> ' . \Yii::t('main', 'Authentication Methods'),
                        'url' => ['/auth/index'],
                        'visible' => Yii::$app->user->can('auth/index'),
                    ],
                ],
            ],

            [
                'label' => 'DEV',
                'visible' => YII_ENV_DEV,
                'options' => ['class' => 'dev_item'],
                'items' => [
                    [
                        'label' => 'Send Events',
                        'url' => ['/test/send'],
                        'options' => ['class' => 'dev_item'],
                        'visible' => YII_ENV_DEV,
                    ],
                    [
                        'label' => 'Send AgentEvents',
                        'url' => ['/test/agent'],
                        'options' => ['class' => 'dev_item'],
                        'visible' => YII_ENV_DEV,
                    ],
                    [
                        'label' => 'Listen to Events',
                        'url' => ['/test/listen'],
                        'options' => ['class' => 'dev_item'],
                        'visible' => YII_ENV_DEV,
                    ],
                ]
            ],

            [
                'label' => \Yii::t('main', 'Help'),
                'url' => ['/howto/index.md'],
                'visible' => !Yii::$app->user->isGuest,
            ],

            Yii::$app->user->isGuest ?
                ['label' => \Yii::t('main', 'Login'), 'url' => ['/site/login']] :
                [
                    'label' => \Yii::t('main', 'Logout') . ' (' . Yii::$app->user->identity->username . ')',
                    'url' => ['/site/logout'],
                    'linkOptions' => ['data-method' => 'post']
                ],
        ],
    ]);
    NavBar::end();
    ?>

    <div class="container">
        <?= Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
        ]) ?>
        <?= $content ?>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <p class="pull-left">&copy; GLaDOS <?= ' ' . \Yii::$app->version . ' - ' . date('Y') ?>
            <?= ActiveEventField::widget([
                'options' => [
                    'tag' => 'i',
                    'class' => 'glyphicon glyphicon-stop',
                ],
                'event' => 'meta',
                'jsonSelector' => 'state',
                'jsHandler' => 'function(d, s){
                    if(d == "event stream started" || d == "event stream resumed"){
                        s.classList.remove("glyphicon-stop", "glyphicon-pause");
                        s.classList.add("glyphicon-play");
                    }else if(d == "event stream finished"){
                        s.classList.remove("glyphicon-play");
                        s.classList.add("glyphicon-pause");
                    }
                }'
            ]); ?>
        </p>

        <p class="pull-right"><?= Yii::powered() ?></p>
    </div>
</footer>

<?php //echo $this->render('@app/views/_events'); ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
