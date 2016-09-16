<?php

/* @var $this yii\web\View */
/* @var $model app\models\EventItem */

if($model->id){
    echo "id: " . $model->id . PHP_EOL;
}


if($model->event){
    echo "event: " . $model->event . PHP_EOL;
}

if($model->retry){
    echo "retry: " . $model->retry . PHP_EOL;
}

echo 'data: {"data":' . $model->data;
if($model->generated_at){
    echo ',"generated_at":' . $model->generated_at;
}

if($model->sent_at){
    echo ',"sent_at":' . $model->sent_at;
}

if($model->debug){
    echo ',"debug":' . $model->debug;
}

echo '}' . PHP_EOL . PHP_EOL;

?>