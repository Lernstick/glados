<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ListView;

/* @var $this yii\web\View */
/* @var $model app\models\Exam */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Exams', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="exam-view container">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_nav', [
        'model' => $model,
    ]) ?>

    <p></p>
    
	<div class="exam-monitor tab-content">

		<?php $_GET = array_merge($_GET, ['#' => 'tab_monitor']); ?>
	    <?= ListView::widget( [
	        'dataProvider' => $dataProvider,
	        'itemView' => '_item',
	        'itemOptions' => ['sort-value' => 'download_progress'],
	        'emptyText' => 'No tickets found.',
	    	'layout' => '{items} <div class="col-sm-12">{summary} {pager}</div>',
	    ] ); ?>

	</div>

</div>
