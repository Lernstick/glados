<?php
use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use app\components\ActiveEventField;

?>  

<?php ActiveEventField::begin([
    'event' => 'ticket/' . $model->id,
    'jsonSelector' => 'action',
    'jsHandler' => 'function(d, s){if(d == "update"){$.pjax.reload({container: s});}}'
]) ?>

<a data-pjax="0" href="<?= Url::to(['ticket/view', 'id' => $model->id]); ?>">

    <div class="col-sm-4 col-xs-8 list-group-item-<?= array_key_exists($model->state, $model->classMap) ? $model->classMap[$model->state] : 'default'; ?>" style="height:200px; padding-top: 10px;border: 1px solid #D8D8D8;">

        <div class="col-sm-4">State: </div>
        <div class="col-sm-8">
            <span class="label label-<?= array_key_exists($model->state, $model->classMap) ? $model->classMap[$model->state] : 'default'; ?>">
                <?= yii::$app->formatter->format($model->state, 'state'); ?>
            </span>
        </div>

        <div class="col-sm-4">Token: </div>
        <div class="col-sm-8">
            <?= yii::$app->formatter->format($model->token, 'text'); ?>
        </div>

        <div class="col-sm-4">Client State: </div>
        <?= ActiveEventField::widget([
            'content' => yii::$app->formatter->format(StringHelper::truncate($model->client_state, 30), 'text'),
            'event' => 'ticket/' . $model->id,
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

        <div class="col-sm-4">IP Address: </div>
        <div class="col-sm-8">
            <?= yii::$app->formatter->format($model->ip, 'text'); ?>
        </div>

        <div class="col-sm-4">Test Taker: </div>
        <div class="col-sm-8">
            <?= yii::$app->formatter->format(StringHelper::truncate($model->test_taker, 30), 'text'); ?>&nbsp;
        </div>

        <div class="col-sm-4">Download: </div>
        <div class="col-sm-8">
            <div class="progress">
                <?php ActiveEventField::begin([
                    'event' => 'ticket/' . $model->id,
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
                        'jsonSelector' => 'download_progress',
                        'jsHandler' => 'function(d, s){
                            s.innerHTML = d;
                            s.parentNode.style = "width:" + d;
                        }',
                    ]); ?>

                <?php ActiveEventField::end(); ?>

            </div>
        </div>

        <div class="col-sm-4">Backup: </div>
        <?= ActiveEventField::widget([
            'content' => yii::$app->formatter->format(StringHelper::truncate($model->backup_state, 30), 'text'),
            'event' => 'ticket/' . $model->id,
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

    </div>

</a>

<?php ActiveEventField::end(); ?>

