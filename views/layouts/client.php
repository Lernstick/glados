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
    <?php
    if (YII_ENV_DEV) {
        echo "<p class='navbar-text' style='color:red; font-size:10px; margin:10px;'>YII_ENV_DEV=true<br>YII_DEBUG=" . (YII_DEBUG ? 'true' : 'false') . "</p>";
    }
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

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
