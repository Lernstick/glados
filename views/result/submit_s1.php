<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\web\JsExpression;
use limion\jqueryfileupload\JQueryFileUpload;


/* @var $this yii\web\View */

$this->title = 'Submit Results';
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['result/submit']];
$this->params['breadcrumbs'][] = 'Step 1';
$this->title .= ' - Step 1';

?>
<div class="result-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin([
        'options' => ['enctype' => 'multipart/form-data'],
        'method' => 'get',
        'action' => ['result/submit', 'mode' => 'step2'],
    ]); ?>

    <div class="alert alert-success" role="alert">
        <span class="glyphicon glyphicon-alert"></span>
        <span>For more information, please visit <?= Html::a('Manual / Submit results back to the student', ['/howto/view', 'id' => 'submit-results.md'], ['class' => 'alert-link']) ?>.</span>
    </div>

    <div class="media-body">
        <span class="hint-block">Please upload a <b>Results ZIP-file</b>, which has the same directory structure as the generated ZIP-file from this webinterface. Make sure the top level directories have <i>exactly</i> the same name as before. The directories should each be named like this:<br><code>&lt;name&gt; - &lt;token&gt;</code>,<br>where <code>&lt;name&gt;</code> refers to the test takers name and <code>&lt;token&gt;</code> to the unique ticket token.<br>
        If the <code>&lt;name&gt;</code> is not set, the <code>_NoName</code> marker is used instead as a name.<br><br>
        After the submitting process, the test taker will have access to the result as a ZIP-file likewise. The contents of each ZIP-file will be <b><i>everything</i></b> inside the corresponding directory of the Results ZIP-file. So make sure there is no secret content in it.</span>
    </div>
    <hr>

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
                'mainView'=>'@app/views/result/_upload_main',
                'uploadTemplateView'=>'@app/views/result/_upload_upload',
                'downloadTemplateView'=>'@app/views/result/_upload_download',
                'options' => [
                    'multiple' => false
                ],
                'clientOptions' => [
                    'maxFileSize' => 4000000000,
                    'dataType' => 'json',
                    'acceptFileTypes' => new yii\web\JsExpression('/(\.|\/)(zip)$/i'),
                    'maxNumberOfFiles' => 1,
                    'autoUpload' => true
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
