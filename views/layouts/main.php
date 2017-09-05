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
        echo "<p class='navbar-text' style='color:red; font-size:10px; margin:10px;'>YII_ENV_DEV=true<br>YII_DEBUG=" . (YII_DEBUG ? 'true' : 'false') . "</p>";
    }
    echo Nav::widget([
        'options' => ['class' => 'navbar-nav navbar-right'],
        'items' => [
            ['label' => 'Home', 'url' => ['/site/index']],
            [
                'label' => 'Actions',
                'visible' => !Yii::$app->user->isGuest,
                'items' => [
                    [
                        'label' => 'Create User',
                        'url' => ['/user/create'],
                        'visible' => Yii::$app->user->can('user/create'),
                    ],                
                    [
                        'label' => 'Create Exam',
                        'url' => ['/exam/create'],
                        'visible' => Yii::$app->user->can('exam/create'),
                    ],
                    [
                        'label' => 'Create single Ticket',
                        'url' => ['/ticket/create', 'mode' => 'single'],
                        'visible' => Yii::$app->user->can('ticket/create'),
                    ],
                    [
                        'label' => 'Create multiple Tickets',
                        'url' => ['/ticket/create', 'mode' => 'many', 'type' => 'assigned'],
                        'visible' => Yii::$app->user->can('ticket/create'),
                    ],                    
                    [
                        'label' => 'Submit Ticket',
                        'url' => ['/ticket/update', 'mode' => 'submit'],
                        'visible' => Yii::$app->user->can('ticket/update'),                    
                    ],
                    [
                        'label' => 'Start Daemon',
                        'url' => ['/daemon/create', 'type' => 'daemon'],
                        'visible' => Yii::$app->user->can('daemon/create'),
                    ],

                    [
                        'label' => 'Generate results',
                        'url' => ['/result/generate'],
                        'visible' => Yii::$app->user->can('exam/view'),
                    ],
                    [
                        'label' => 'Submit results',
                        'url' => ['/result/submit'],
                        'visible' => Yii::$app->user->can('result/submit'),
                    ],
                ],
            ],
            [
                'label' => 'Activities ' . 
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
                'label' => 'Exams',
                'url' => ['/exam/index'],
                'visible' => Yii::$app->user->can('exam/index'),
            ],
            [
                'label' => 'Tickets',
                'url' => ['/ticket/index'],
                'visible' => Yii::$app->user->can('ticket/index'),
            ],
            [
                'label' => 'Daemons ' . 
                    ActiveEventField::widget([
                        'content' => $runningDaemons,
                        'options' => [
                            'class' => 'badge',
                            'change-animation' => 'bounce 1000ms linear both',
                        ],
                        'event' => 'runningDaemons',
                        'jsonSelector' => 'runningDaemons',
                    ]),
                'encode' => false,
                'url' => ['/daemon/index'],
                'visible' => Yii::$app->user->can('daemon/index'),
            ],
            [
                'label' => 'Users',
                'url' => ['/user/index'],
                'visible' => Yii::$app->user->can('user/index'),
            ],
            [
                'label' => 'Profile',
                'url' => ['/user/view', 'id' => Yii::$app->user->id],
                'visible' => !Yii::$app->user->isGuest && Yii::$app->user->can('user/view'),
            ],

            [
                'label' => 'Config',
                'url' => ['/config/system'],
                'visible' => Yii::$app->user->can('config/system'),
            ],

            [
                'label' => 'Help',
                'url' => ['/howto/index'],
                'visible' => !Yii::$app->user->isGuest,
            ],

            Yii::$app->user->isGuest ?
                ['label' => 'Login', 'url' => ['/site/login']] :
                [
                    'label' => 'Logout (' . Yii::$app->user->identity->username . ')',
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
        <p class="pull-left">&copy; GLaDOS <?= ' ' . \Yii::$app->params['version'] . ' - ' . date('Y') ?>
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
