<?php

/* @var $this yii\web\View */

use yii\helpers\Html;
use app\models\Setting;

$this->title = \Yii::t('app', 'Lock screen');
?>
<div class="site-lock">
    <div class="row">
        <div class="col-lg-3"></div>
        <div class="col-lg-6">
            <h1><?= Html::encode(Yii::t("exam", "Your screen is currently locked by the supervisor")) ?></h1>
            <?= Setting::get('lockText'); ?>
        </div>
        <div class="col-lg-3"></div>
    </div>
</div>