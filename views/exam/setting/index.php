<?php

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\dynagrid\DynaGrid;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ExamSettingAvail */
/* @var $dataProvider yii\data\ActiveDataProvider */

$js = <<< SCRIPT
$("[id^=ExamSettings_][id$=_key]").each(function (){
    if ($(this).attr('id') != "ExamSettings___id___key") {
        var keyA = $(this).find(':selected').attr('value');
        $(".settingClick").each(function () {
            var keyB = $(this).attr('key');
            if (keyA == keyB) {
                $(this).addClass('disabled');
            }
        });
    }
});

$(".settingClick:not(.disabled)").each(function () {
    $(this).on('click', function () {
        var id = "new" + setting_k;
        var text = $(this).find('td').html();
        var key = $(this).attr('key');

        // select the item in the dropdown list
        $("#item" + id).siblings('.key').addClass('hidden');
        var newOption = new Option(text, key, true, true);
        $("#ExamSettings_" + id + "_key").append(newOption).trigger('change');

        $.pjax.reload({
            container: "#item" + id,
            fragment: "body",
            type: 'POST',
            data: {
                'setting[id]': id,
                'setting[key]': key,
                '_csrf': $("input[name='_csrf']").val()
            },
            async:true
        });
        $('#keyModal').modal('hide');
    });
});

SCRIPT;
$this->registerJs($js);

?>
<div class="exam-settings-index">

    <?= DynaGrid::widget([
        'showPersonalize' => false,
        'columns' => [
            'name',
            'description:html',
        ],
        'storage' => DynaGrid::TYPE_COOKIE,
        'theme' => 'simple-default',
        'gridOptions' => [
            'dataProvider' => $dataProvider,
            //'filterModel' => $searchModel,
            'layout' => '{items}<br>{pager}',
            'rowOptions' => function ($model, $key, $index, $grid) {
                return [
                    'class' => 'settingClick',
                    'id' => $model->id,
                    'key' => $model->key,
                ];
            },
        ],
        'options' => ['id' => 'dynagrid-exam-settings-index'] // a unique identifier is important
    ]); ?>

</div>
