<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\web\JsExpression;
use limion\jqueryfileupload\JQueryFileUpload;


/* @var $this yii\web\View */

$this->title = 'Submit Results';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="result-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin([
    	'options' => ['enctype' => 'multipart/form-data'],
    	'method' => 'get',
        'action' => ['result/submit', ['mode' => 'step2']],    
    ]); ?>

    <div class="row">
        <div class="col-md-12">
            <?php

            echo Html::label('Result ZIP-File');
            echo JQueryFileUpload::widget([
            	'model' => $model,
                'name' => 'file',
                'url' => ['submit', 'mode' => 'upload'],
                'appearance' => 'ui',
                'formId' => $form->id,
        		'downloadTemplateView'=>'@app/views/result/download',
                'options' => [
                    'multiple' => false
                ],
                'clientOptions' => [
                    'maxFileSize' => 4000000000,
                    'dataType' => 'json',
                    'acceptFileTypes' => new yii\web\JsExpression('/(\.|\/)(zip)$/i'),
                    'maxNumberOfFiles' => 1,
                    'autoUpload' => false
                ],
            ]);

            ?>

        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton('Next Step', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
