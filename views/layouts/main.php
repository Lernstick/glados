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
dmstr\web\AdminLteAsset::register($this);
$directoryAsset = Yii::$app->assetManager->getPublishedUrl('@vendor/almasaeed2010/adminlte/dist');

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
<body class="hold-transition skin-blue sidebar-mini">
<?php $this->beginBody() ?>

    <div class="wrapper">
        <?= $this->render('header.php', [
            'directoryAsset' => $directoryAsset
        ]); ?>

        <?= $this->render('left.php', [
            'directoryAsset' => $directoryAsset,
            'newActivities' => $newActivities,
            'runningDaemons' => $runningDaemons,
        ]); ?>

        <?= $this->render('content.php', [
            'content' => $content,
            'directoryAsset' => $directoryAsset
        ]); ?>
    </div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
