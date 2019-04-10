<?php
use yii\helpers\Html;
use yii\widgets\Pjax;
use app\components\ActiveEventField;

?>  

    <div class="col-sm-12 col-xs-12" style="height:100px; padding-top: 10px;border: 1px solid #D8D8D8;">

        <span>
            <?= \Yii::t('daemons', 'Process') ?>: <?= yii::$app->formatter->format($model->description, 'text'); ?>
            (<?= yii::$app->formatter->format($model->pid, 'text'); ?>), started:
            <?= yii::$app->formatter->format($model->started_at, 'relativetime'); ?>
        </span><br>
        <span>
            <?= \Yii::t('daemons', 'Load') ?>: 
            <?php echo ActiveEventField::widget([
                'id' => 'wdl' . $model->id,
                'options' => [ 'tag' => 'span' ],
                'content' => yii::$app->formatter->format($model->load, 'percent'),
                'event' => 'daemon/' . $model->pid,
                'jsonSelector' => 'load',
            ]); ?>
        </span>, 
        <span>
            <?= \Yii::t('daemons', 'State') ?>: 
            <?php echo ActiveEventField::widget([
                'id' => 'wd' . $model->id,
                'options' => [ 'tag' => 'span' ],
                'content' => yii::$app->formatter->format($model->state, 'text'),
                'event' => 'daemon/' . $model->pid,
                'jsonSelector' => 'state',
            ]); ?>
        </span><br>


        <?php Pjax::begin([
            'id' => 'wdb' . $model->id,
            'enablePushState' => false,
        ]); ?>
            <?= Html::a(\Yii::t('daemons', 'View'), ['view', 'id' => $model->id], ['class' => 'btn btn-primary', 'data-pjax' => 0]) ?>
            <?= Html::a(\Yii::t('daemons', 'Stop'), ['stop', 'id' => $model->id], ['class' => 'btn btn-danger']) ?>
            <?= Html::a(\Yii::t('daemons', 'Kill'), ['kill', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => \Yii::t('daemons', 'Are you sure you want to kill this process?'),
                ],
            ]) ?>
        <?php Pjax::end(); ?>
    </div>    


