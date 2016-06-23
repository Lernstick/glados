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
    echo Nav::widget([
        'options' => ['class' => 'navbar-nav navbar-right'],
        'items' => [
            ['label' => 'Home', 'url' => ['/site/index']],
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
                'url' => ['/daemon/index']
            ],
            [
                'label' => 'Users',
                'url' => ['/user/index'],
                'visible' => Yii::$app->user->can('user/index'),
            ],
            [
                'label' => 'Profile',
                'url' => ['/user/view', 'id' => Yii::$app->user->id],
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
        <p class="pull-left">&copy; GLaDOS <?= date('Y') ?></p>

        <p class="pull-right"><?= Yii::powered() ?></p>
    </div>
</footer>

<?php echo $this->render('@app/views/_events'); ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
