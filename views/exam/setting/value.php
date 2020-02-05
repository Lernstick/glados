<?php

use yii\helpers\Html;
use yii\web\JsExpression;
use yii\widgets\Pjax;
use yii\widgets\ActiveForm;
use yii\base\ViewNotFoundException;

/* @var $id integer */
/* @var $form yii\widgets\ActiveForm */
/* @var $setting app\models\ExamSetting */
/* @var $members app\models\ExamSetting[] */

if ($form == null) {
    $form = ActiveForm::begin([
        'options' => ['enctype' => 'multipart/form-data'],
        'enableClientValidation' => false,
    ]);
}

$type = $setting->detail === null ? 'default' : $setting->detail->type;
if (empty($members)) {
    $members = $setting->members;
}

if (is_file(Yii::getAlias('@app/views/exam/setting/' . $setting->key) . '.php')) {

    echo $this->render($setting->key, [
        'id' => $id,
        'form' => $form,
        'setting' => $setting,
        'members' => $members,
    ]);

} else if (is_file(Yii::getAlias('@app/views/exam/setting/' . $type) . '.php')) {

    echo $this->render($type, [
        'id' => $id,
        'form' => $form,
        'setting' => $setting,
        'members' => $members,
    ]);

} else {

    echo $this->render('default', [
        'id' => $id,
        'form' => $form,
        'setting' => $setting,
        'members' => $members,
    ]);

}


?>