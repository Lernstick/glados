<?php

use kartik\growl\Growl;
use kartik\growl\GrowlAsset;

/* If no flash is defined, no asset is loaded. This forces loading. */
GrowlAsset::register($this);

/* @var $this yii\web\View */
/* @var $model app\models\Ticket */

$session = Yii::$app->session;

$n = [
    'success' => $session->getFlash('success'),
    'info' => $session->getFlash('info'),
    'warning' => $session->getFlash('warning'),
    'danger' => $session->getFlash('danger'),
];

$types = [
    'success' => Growl::TYPE_SUCCESS,
    'info' => Growl::TYPE_INFO,
    'warning' => Growl::TYPE_WARNING,
    'danger' => Growl::TYPE_DANGER,
];


$icons = [
    'success' => 'glyphicon glyphicon-ok-sign',
    'info' => 'glyphicon glyphicon-info-sign',
    'warning' => 'glyphicon glyphicon-warning-sign',
    'danger' => 'glyphicon glyphicon-exclamation-sign',
];

foreach ($n as $category => $array){

    if($n[$category]){

        foreach ($n[$category] as $id => $value){

            echo Growl::widget([
                'type' => isset($types[$category]) ? $types[$category] : Growl::TYPE_INFO,
                'icon' => isset($icons[$category]) ? $icons[$category] : null,
                'title' => $category,
                'showSeparator' => true,
                'body' => $value,
                'pluginOptions' => [
                    'showProgressbar' => true,
                    'placement' => [
                        'from' => 'top',
                        'align' => 'right',
                    ]
                ]
            ]);
        }
    }
}

?>
