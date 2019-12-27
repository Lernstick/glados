<?php

use app\models\Ticket;

/* @var $this yii\web\View */
/* @var $model app\models\Setting */

?>

<?= Yii::t('setting', 'Example tokens:'); ?>
<ul>
    <li><?= Ticket::generateRandomToken(); ?></li>
    <li><?= Ticket::generateRandomToken(); ?></li>
    <li><?= Ticket::generateRandomToken(); ?></li>
    <li><?= Ticket::generateRandomToken(); ?></li>
    <li><?= Ticket::generateRandomToken(); ?></li>
</ul>