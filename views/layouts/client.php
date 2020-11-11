<?php

/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use yii\widgets\Breadcrumbs;
use app\assets\AppAsset;
use yii\bootstrap\BootstrapPluginAsset;
use app\components\ActiveEventField;


AppAsset::register($this);
BootstrapPluginAsset::register($this);

/* register the global YII_ENV variables */
$this->registerJs('var YII_ENV_DEV = ' . (YII_ENV_DEV ? 'true' : 'false') . ';', \yii\web\View::POS_HEAD);
$this->registerJs('var YII_DEBUG = ' . (YII_DEBUG ? 'true' : 'false') . ';', \yii\web\View::POS_HEAD);

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=300, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= $this->title?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="wrap">
	<?php if (YII_ENV_DEV) {
        echo "<p class='navbar-text' style='color:red; font-size:7px; margin:10px;'>YII_ENV_DEV=true<br>YII_DEBUG=" . (YII_DEBUG ? 'true' : 'false') . "<br>LANG=" . \Yii::$app->language . "</p>";
    } ?>
    <div class="container">
        <?= $content ?>
    </div>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
