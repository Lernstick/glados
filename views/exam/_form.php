<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
//use kartik\file\FileInput;
use yii\web\JsExpression;
use limion\jqueryfileupload\JQueryFileUpload;

/* @var $this yii\web\View */
/* @var $model app\models\Exam */
/* @var $form yii\widgets\ActiveForm */

if($model->file && Yii::$app->file->set($model->file)->exists) {
    $js = new JsExpression("
        var files = [
            {
                'name':'" . basename($model->file) . "',
                'size':" . filesize($model->file) . ",
                'deleteUrl':'index.php?r=exam/delete&id=" . $model->id . "&mode=squashfs&file=" . basename($model->file) . "',
                'deleteType':'POST'
            }
        ];
    ");
    $this->registerJs($js);
}


?>

<div class="exam-form">

    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'subject')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'grp_netdev')->checkbox() ?>

    <?= $form->field($model, 'allow_sudo')->checkbox() ?>

    <?= $form->field($model, 'allow_mount')->checkbox() ?>

    <?= $form->field($model, 'firewall_off')->checkbox() ?>

    <?= $form->field($model, 'screenshots')->checkbox() ?>

    <?= $form->field($model, 'url_whitelist')->textarea() ?>

    <?php /* echo $form->field($model, 'file')->widget(FileInput::classname(), [
        'options' => ['accept' => '.squashfs'],
        'pluginOptions' => [
            'initialCaption' => $model->isNewRecord ? null: basename($model->file),
            'initialPreview' => $model->isNewRecord ? [] : [ '<span class="glyphicon glyphicon-file kv-caption-icon"></span>' ],
            'overwriteInitial' => true,
            'showPreview' => false,
            'showCaption' => true,
            'showUpload' => false,
            'showRemove' => false,
        ],
    ]);*/ ?>

    <?php
    if(!$model->isNewRecord) {

        echo Html::label('File');
        echo JQueryFileUpload::widget([
            'model' => $model,
            'name' => 'file',
            'url' => ['update', 'id' => $model->id, 'mode' => 'upload'],
            'appearance' => 'ui', // available values: 'ui','plus' or 'basic'
            'formId' => $form->id,
            'options' => [
                'multiple' => false
            ],
            'clientOptions' => [
                'maxFileSize' => 4000000000,
                'dataType' => 'json',
                'acceptFileTypes' => new yii\web\JsExpression('/(\.|\/)(squashfs)$/i'),
                'maxNumberOfFiles' => 1,
                'autoUpload' => false
            ],
        ]);
    }
    ?>

    <?php
    if($model->file && Yii::$app->file->set($model->file)->exists) {
        $js = new JsExpression("var fupload = jQuery('#w0').fileupload({
            'maxFileSize':4000000000,
            'dataType':'json',
            'acceptFileTypes':/(\.|\/)(squashfs)$/i,
            'maxNumberOfFiles':1,
            'autoUpload':false,
            'url':'\/index.php?r=exam%2Fupdate\u0026id=" . $model->id . "\u0026mode=upload',
            progressServerRate: 0.5,
            progressServerDecayExp: 3.5
        });
        jQuery('#w0').fileupload('option', 'done').call(fupload, $.Event('done'), {result: {files: files}});");
        $this->registerJs($js);
    }
    ?>



    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Next Step' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
