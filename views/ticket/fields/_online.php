<?php

use app\components\ActiveEventField;

/* @var $this yii\web\View */
/* @var $model app\models\Ticket*/

echo ActiveEventField::widget([
    'options' => [
        'tag' => 'span',
        'class' => 'label label-' . ( $model->online === 1 ? 'success' :
                                   ( $model->online === 0 ? 'danger' : 
                                                            'warning') )
    ],
    'content' => ( $model->online === 1 ? \Yii::t('ticket', 'Online') :
                 ( $model->online === 0 ? \Yii::t('ticket', 'Offline') : 
                                          \Yii::t('ticket', 'Unknown')) ),
    'event' => 'ticket/' . $model->id,
    'jsonSelector' => 'online',
    'jsHandler' => 'function(d, s){
        if(d == "1"){
            s.innerHTML = "' . \Yii::t('ticket', 'Online') . '";
            s.classList.add("label-success");
            s.classList.remove("label-danger");
            s.classList.remove("label-warning");
        }else if(d == "0"){
            s.innerHTML = "' . \Yii::t('ticket', 'Offline') . '";
            s.classList.add("label-danger");
            s.classList.remove("label-success");
            s.classList.remove("label-warning");
        }
    }',
]);

