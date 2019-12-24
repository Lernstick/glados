<?php

use yii\widgets\Pjax;
use app\models\Ticket;

/* @var $this yii\web\View */
/* @var $model app\models\Setting */

$ticket = new Ticket();

?>

<?php Pjax::begin([
    'id' => 'preview'
]); ?>

<?= Yii::t('setting', 'Example Token: {token}', ['token' => $ticket->token]); ?>

<?php Pjax::end(); ?>
