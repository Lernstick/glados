<?php

use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $model app\models\Setting */

?>

<?php Pjax::begin([
    'id' => 'preview'
]); ?>

<object type="text/html">
    <?= $this->render('/site/login', [
        'model' => new \app\models\LoginForm()
    ]); ?>
</object>

<?php Pjax::end(); ?>
