<?php
use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use app\components\ActiveEventField;

?>  

<?php $f = ActiveEventField::begin([
    'event' => 'ticket/' . $model->id,
    'marker' => 'monitor',
    'jsonSelector' => 'action',
    'jsHandler' => 'function(d, s){if(d == "update"){$.pjax.reload(s);}}'
]) ?>

<a data-pjax="0" href="<?= Url::to(['ticket/view', 'id' => $model->id]); ?>">

    <div class="col-sm-4 col-xs-8 list-group-item-<?= array_key_exists($model->state, $model->classMap) ? $model->classMap[$model->state] : 'default'; ?>" style="height:170px; padding:10px 0px 10px 0px; border: 1px solid #D8D8D8;">

        <div class="col-sm-4">State: </div>
        <div class="col-sm-8">
            <span class="label label-<?= array_key_exists($model->state, $model->classMap) ? $model->classMap[$model->state] : 'default'; ?>">
                <?= yii::$app->formatter->format($model->state, 'state'); ?>
            </span>
        </div>

        <div class="col-sm-4"><?= \Yii::t('exams', 'Token') ?>: </div>
        <div class="col-sm-8">
            <?= yii::$app->formatter->format($model->token, 'text'); ?>
        </div>

        <div class="col-sm-4"><?= \Yii::t('exams', 'Client State') ?>: </div>
        <?= ActiveEventField::widget([
            'content' => yii::$app->formatter->format(StringHelper::truncate($model->client_state, 30), 'text'),
            'event' => 'ticket/' . $model->id,
            'marker' => 'monitor',
            'jsonSelector' => 'client_state',
            'jsHandler' => 'function(d, s){
                if (d.length > 30) {
                    s.innerHTML = d.substr(0, 30) + "...";
                }else{
                    s.innerHTML = d;
                }
            }',  
            'options' => [ 'class' => 'col-sm-8' ],
        ]); ?>


        <div class="col-sm-4"><?= \Yii::t('exams', 'IP Address') ?>: </div>
        <div class="col-sm-8">
            <?= yii::$app->formatter->format($model->ip, 'text'); ?>
        </div>

        <div class="col-sm-4"><<?= \Yii::t('exams', 'Test Taker') ?>: </div>
        <div class="col-sm-8">
            <?= yii::$app->formatter->format(StringHelper::truncate($model->test_taker, 30), 'text'); ?>&nbsp;
        </div>

        <div class="col-sm-4"><?= \Yii::t('exams', 'Backup') ?>:
            <?= ActiveEventField::widget([
                'event' => 'ticket/' . $model->id,
                'marker' => 'monitor',
                'jsonSelector' => 'backup_lock',
                'jsHandler' => 'function(d, s){
                    if(d == "1"){
                        s.style.display = "";
                    }else if(d == "0"){
                        s.style.display = "none";
                    }
                }',           
                'options' => [
                    'class' => 'glyphicon glyphicon-cog gly-spin',
                    'style' => ['display' => $model->backup_lock == 1 ? '' : 'none'],
                    'tag' => 'i',
                ],
            ]); ?>
        </div>
        <?= ActiveEventField::widget([
            'content' => yii::$app->formatter->format(StringHelper::truncate($model->backup_state, 30), 'text'),
            'event' => 'ticket/' . $model->id,
            'marker' => 'monitor',
            'jsonSelector' => 'backup_state',
            'jsHandler' => 'function(d, s){
                if (d.length > 30) {
                    s.innerHTML = d.substr(0, 30) + "...";
                }else{
                    s.innerHTML = d;
                }
            }',            
            'options' => [ 'class' => 'col-sm-8' ],
        ]); ?>

        <div class="col-sm-4"><?= \Yii::t('exams', 'Download') ?>: </div>
        <div class="col-sm-8">
            <div class="progress">
                <?php ActiveEventField::begin([
                    'event' => 'ticket/' . $model->id,
                    'marker' => 'monitor',
                    'jsonSelector' => 'download_lock',
                    'jsHandler' => 'function(d, s){
                        if(d == "1"){
                            s.classList.add("active");
                        }else if(d == "0"){
                            s.classList.remove("active");
                        }
                    }',
                    'options' => [
                        'class' => 'progress-bar progress-bar-striped ' . ($model->download_lock == 1 ? 'active' : null),
                        'role' => 'progressbar',
                        'aria-valuenow' => '0',
                        'aria-valuemin' => '0',
                        'aria-valuemax' => '100',
                        'style' => 'width:' . yii::$app->formatter->format($model->download_progress, 'percent') . ';',
                    ]
                ]); ?>

                    <?= ActiveEventField::widget([
                        'options' => [ 'tag' => 'span' ],
                        'content' => yii::$app->formatter->format($model->download_progress, 'percent'),
                        'event' => 'ticket/' . $model->id,
                        'marker' => 'monitor',
                        'jsonSelector' => 'download_progress',
                        'jsHandler' => 'function(d, s){
                            s.innerHTML = d;
                            s.parentNode.style = "width:" + d;
                        }',
                    ]); ?>

                <?php ActiveEventField::end(); ?>

            </div>
        </div>

    </div>

</a>

<?php ActiveEventField::end(); ?>

