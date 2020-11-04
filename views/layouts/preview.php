<?php

/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use app\assets\AppAsset;

AppAsset::register($this);

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

    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<div class="wrap">
    <div class="container" style="padding-top:0px; padding-bottom:0px;">
        <?= $content ?>
    </div>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
