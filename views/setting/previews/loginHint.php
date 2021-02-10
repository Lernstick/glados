<?php

/* @var $this yii\web\View */
/* @var $model app\models\Setting */

?>

<?= $this->render('/site/login', [
    'model' => new \app\models\LoginForm()
]); ?>
