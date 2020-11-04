<?php

/* @var $this yii\web\View */
/* @var $model app\models\Setting */

?>

<object type="text/html">
    <?= $this->render('/site/login', [
        'model' => new \app\models\LoginForm()
    ]); ?>
</object>
