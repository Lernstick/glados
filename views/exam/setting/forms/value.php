<?php

use yii\helpers\Html;
use yii\web\JsExpression;
use yii\widgets\Pjax;
use yii\widgets\ActiveForm;
use yii\base\ViewNotFoundException;

/* @var $id integer */
/* @var $form yii\widgets\ActiveForm */
/* @var $setting app\models\ExamSetting */
/* @var $model app\models\forms\ExamForm */

// the hints should be reinitialized (ajax request)
Yii::$app->request->isAjax ? $this->registerJs('initializeHints();') : null;

/**
 * if it's an ajax request we have to render using the renderAjax()
 * method, to inject javascript assets.
 */
$render = Yii::$app->request->isAjax ? 'renderAjax': 'render';
$view = 'default';

if ($form == null) {
    $form = ActiveForm::begin([
        'options' => ['enctype' => 'multipart/form-data'],
        'enableClientValidation' => false,
    ]);
}

$type = $setting->detail === null ? 'default' : $setting->detail->type;
$label = $setting->detail === null ? \Yii::t('exams', 'Value') : $setting->detail->name;
$hint = $setting->detail === null ? null : $setting->detail->description;

// if there are no members set, retrieve the member default values from the database
// and combine them as ['key' => ExamSetting]
$members = array_filter($model->examSettings, function($el) use ($id) {
    return $id == $el->belongs_to;
});
$keys = array_map(function($v){return $v->key;}, $members);
$members = array_combine($keys, $members);

if (empty($members)) {
    if (empty($setting->members) && $setting->detail !== null) {
        $members = [];
        foreach ($setting->detail->members as $detail) {
            $members[$detail->key] = new app\models\ExamSetting([
                'key' => $detail->key,
            ]);
            $members[$detail->key]->loadDefaultValue();
        }
    } else {
        $members = array_combine(
            array_map(function($v){return $v->key;}, $setting->members),
            array_map(function($v){return $v;}, $setting->members)
        );
    }
}

if (is_file(Yii::getAlias('@app/views/exam/setting/forms/' . $setting->key) . '.php')) {
    $view = $setting->key;
} else if (is_file(Yii::getAlias('@app/views/exam/setting/forms/' . $type) . '.php')) {
    $view = $type;
}

// if there are members, wrap the setting in a panel
if (!empty($members) && $view !== $setting->key) {
    echo '<div class="panel panel-default"><div class="panel-heading">';
}

echo $this->{$render}($view, [
    'id' => $id,
    'form' => $form,
    'setting' => $setting,
    'members' => $members,
    'label' => $label,
    'hint' => $hint,
]);

if (!empty($members) && $view !== $setting->key) {
    echo '</div><div class="panel-body">';
}

if ($view != $setting->key) {
    $i = 'a';
    foreach ($members as $subSetting) {
        $idx = strpos($id, 'new') === 0 ? $id . $i : $subSetting->id;

        echo $this->render('key_hidden', [
            'id' => $idx,
            'belongs_to' => $id,
            'form' => $form,
            'setting' => $subSetting,
        ]);

        echo $this->render('value', [
            'id' => $idx,
            'form' => $form,
            'setting' => $subSetting,
            'model' => $model,
        ]);
        $i++;
    }
}

if (!empty($members) && $view !== $setting->key) {
    echo '</div></div>';
}

?>