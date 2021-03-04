<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Json;

/* @var $this yii\web\View */
/* @var $model app\models\forms\ElasticsearchQuery */
/* @var $response mixed */
/* @var $host mixed */

$this->title = 'Query Elasticsearch';
$this->params['breadcrumbs'][] = $this->title;

if ($model->isNewRecord) {
    $model->method = 'get';
    $model->url = '_cat/indices';
    $model->data = '';
}

if ($online !== true) {
    echo '<div class="alert alert-danger" role="alert">There is something wrong with the elasticsearch configuration!</div>';
}

?>
<div class="event-form">
    <div class="row">
        <div class="col-md-6">
            <?php $form = ActiveForm::begin(); ?>
            <?= $form->field($model, 'method')->textInput() ?>
            <?= $form->field($model, 'url')->textInput() ?>
        </div>
        <div class="col-md-6">        
            <?= $form->field($model, 'data')->textArea() ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <?= Html::submitButton('Query Elasticsearch', ['class' => 'btn btn-primary']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<hr>

cURL command: <br>
<pre style="white-space: pre-wrap;">
<?php
echo 'curl -X ' . strtoupper($model->method) . ' "' . $host . "/" . $model->url . '"';
if (!empty($model->data)) {
    $d = json_encode(json_decode($model->data));
    echo " -H 'Content-Type: application/json' -d'" . $d . "'";
}
?>
</pre>

Response: <br>
<pre style="white-space: pre-wrap;">
<?php 
if (is_string($response)){
    try {
        echo JSON::encode(JSON::decode($response), JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        echo $response;
    }
} else if (is_array($response)){
    echo JSON::encode($response, JSON_PRETTY_PRINT);
} else {
    var_dump($response);
}

?>
</pre>
