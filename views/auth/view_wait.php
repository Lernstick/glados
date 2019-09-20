<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\Pjax;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $id integer the id */

$this->title = Yii::t('auth', 'Entry {id}', ['id' => $id]);
$this->params['breadcrumbs'][] = ['label' => \Yii::t('auth', 'Authentication Methods'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$js = <<<JS
// Change hash for page-reload
setTimeout(function(){
   window.location.reload(1);
}, 5000);
JS;
$this->registerJs($js);

?>
<div class="auth-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="alert alert-info" role="alert"><?= Yii::t('auth', 'The entry is beeing processed. This may take a while, please wait ...') ?></div>

</div>
