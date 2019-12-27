<?php

use app\models\Ticket;

/* @var $this yii\web\View */
/* @var $model app\models\Setting */

$ticket = new Ticket();

?>

<?= Yii::t('setting', 'Example Token: {token}', ['token' => $ticket->token]); ?>
