<?php

use yii\helpers\Html;
use yii\web\JsExpression;
use yii\widgets\Pjax;
use yii\widgets\ActiveForm;
use yii\base\ViewNotFoundException;

/* @var $id integer */
/* @var $form yii\widgets\ActiveForm */
/* @var $setting app\models\ExamSetting */

$formBegin = false;
if ($form == null) {
    $formBegin = true;
    $form = ActiveForm::begin([
        'options' => ['enctype' => 'multipart/form-data'],
        'enableClientValidation' => false,
    ]);
}

if (is_file(Yii::getAlias('@app/views/exam/setting/' . $setting->key) . '.php')) {

    echo $this->render($setting->key, [
        'id' => $id,
        'form' => $form,
        'setting' => $setting,
    ]);

} else {

    echo $this->render('default', [
        'id' => $id,
        'form' => $form,
        'setting' => $setting
    ]);

}

//if ($formBegin) {
    //ActiveForm::end();
//}


?>