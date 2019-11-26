<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\Pjax;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $id integer the id */

$this->title = Yii::t('auth', 'Processing entry {id}', ['id' => $id]);
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

    <br><br>
    <div class="row">
        <div class="col-sm-3"></div>
        <div class="alert alert-info col-sm-6 text-center" role="alert">
            <i class="glyphicon glyphicon-cog gly-spin"></i>&nbsp;<span><?= Yii::t('auth', 'The change is beeing processed. This may take a while, please wait ...') ?></span>
        </div>
        <div class="col-sm-3"></div>
    </div>
    <div class="row">
        <div class="col-sm-12 text-center">
            <a class="btn btn-default" href="" data-pjax="0"><i class="glyphicon glyphicon-refresh"></i>&nbsp;<?= Yii::t('app', 'Reload Page') ?></a>
        </div>
    </div>

</div>
