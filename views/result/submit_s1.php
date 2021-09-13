<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\web\JsExpression;
use limion\jqueryfileupload\JQueryFileUpload;


/* @var $this yii\web\View */
/* @var $model Result */

$this->title = \Yii::t('results', 'Submit Results');
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['result/submit']];
$this->params['breadcrumbs'][] = \Yii::t('results', 'Step 1');
$this->title .= ' - ' . \Yii::t('results', 'Step 1');

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
        <span><?= \Yii::t('results', 'For more information, please visit {link}.', [
            'link' => Html::a('Manual / Submit results back to the student', ['/howto/view', 'id' => 'submit-results.md'], ['class' => 'alert-link', 'target' => '_new'])
        ]) ?></span>
    </div>

    <div class="bs-callout bs-callout-success">
        <h4><?= \Yii::t('results', 'Step 1') ?></h4>
        <p><?= \Yii::t('results', 'Please upload a <b>Results ZIP-file</b>, which has the same directory structure as the generated ZIP-file from this webinterface. Make sure the top level directories have <i>exactly</i> the same name as before. The directories should each be named like this:<br><code>&lt;name&gt; - &lt;token&gt;</code>,<br>where <code>&lt;name&gt;</code> refers to the test takers name and <code>&lt;token&gt;</code> to the unique ticket token.') ?></p>
        <p><?= \Yii::t('results', 'If the <code>&lt;name&gt;</code> is not set, the <code>_NoName</code> marker is used instead as a name.') ?></p>
        <p><?= \Yii::t('results', 'After the submitting process, the test taker will have access to the result as a ZIP-file likewise. The contents of each ZIP-file will be <b><i>everything</i></b> inside the corresponding directory of the Results ZIP-file. So make sure there is no secret content in it.') ?></p>
    </div>

    <div class="row">
        <div class="col-md-12">
            <?php
            echo Html::label(\Yii::t('results', 'Result ZIP-File'));
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

    <hr>
    <div class="form-group">
        <?= Html::submitButton(\Yii::t('results', 'Next Step') . '<i class="glyphicon glyphicon-chevron-right"></i>', ['class' => 'btn btn-success pull-right']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
