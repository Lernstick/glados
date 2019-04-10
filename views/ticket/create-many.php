<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\web\JsExpression;


/* @var $this yii\web\View */
/* @var $model app\models\Ticket */
/* @var $form yii\widgets\ActiveForm */

$js = <<< 'SCRIPT'
$('.hint-block').each(function () {
    var $hint = $(this);

    $hint.parent().find('label').after('&nbsp<a tabindex="0" role="button" class="hint glyphicon glyphicon-question-sign"></a>');

    $hint.parent().find('a.hint').popover({
        html: true,
        trigger: 'focus',
        placement: 'right',
        //title:  $hint.parent().find('label').html(),
        title:  'Description',
        toggle: 'popover',
        container: 'body',
        content: $hint.html()
    });

    $hint.remove()
});
SCRIPT;
// Register tooltip/popover initialization javascript
$this->registerJs($js);

$js = new JsExpression("
    $('textarea').on('input',function () {
        var fullnames = [];
        var input = $('textarea')[0].value;
        var units = input.split(/\\n\\n+/).filter(function(e){return e});

        //try to parse all units separately, units are separated by two newlines
        for(var o = 0; o < units.length; o++){

            var string = units[o];
            var simple_parsing = false;
            var separators = [ '\\n', '\,', '\;', '\\t' ];

            //loop trough all separators and check if the blocks can be parsed the simple way
            for(var c = 0; c < separators.length; c++){
                simple_parsing_item = true;
                var e = s = 0;
                var sepBased = string.split(separators[c]).filter(function(e){return e}).map(Function.prototype.call, String.prototype.trim);
                var sepBased2 = sepBased;

                for(var i = 0; i < sepBased2.length; i++){
                    for(var z = 0; z < separators.length; z++){
                        if(separators[z] != separators[c]){
							//replace all other separators with a pipe (hope there is no name with a pipe in it...)
                            sepBased2[i] = sepBased2[i].replace(new RegExp(separators[z], 'g'), '|');
                        }
                    }
                    sepBasedParts = sepBased2[i].split(/ *(?:\|| )+ */).filter(function(e){return e});
                    sepBasedSimpleParts = sepBased[i].split(/ /).filter(function(e){return e});

                    if(sepBasedParts.length == sepBasedSimpleParts.length && sepBasedParts.length >= 2 && !sepBased[i].match(/[\\t|\;|\,]/g)){
                        s += sepBasedParts.length
                    }else if(sepBasedParts.length < 2 || sepBasedParts.length > 5 || sepBased[i].match(/[\\t|\;|\,]/g)){
                        e += sepBasedParts.length;
                    }else{
                        s += sepBasedParts.length
                    }
                }

				//if there are more items which would need extended parsing, treat the whole block/unit as one
                if(e > s){
                    simple_parsing_item = false;
                }else{
                    simple_parsing = true;
                    var separator = separators[c];
                }

            }

			/*
			 * actual parsing comes here
			 */

            if(simple_parsing){

				//in case of simple parsing just split at the extracted separator and trim the items
                var items = string.split(separator).filter(function(e){return e}).map(Function.prototype.call, String.prototype.trim);
                for(var i = 0; i < items.length; i++){
                    fullnames.push(items[i].replace(/ *(?:\,|\;|\\t| )+ */g, ' '));
                }

            }else{

                var names = string.split(/ *(?:\,|\\n|\;|\\t|  )+ */).filter(function(e){return e});

                //value that appears most could be the class name -> ignore
                var most = [];
                var max = 1;
                var maxName = '';
                for(var i = 0; i < names.length; i++){
                    if (most[names[i]] == null){
                        most[names[i]] = 1;
                    }else{
                        most[names[i]]++;
                    }

                    if (most[names[i]] > max){
                        maxName = names[i];
                        max = most[names[i]];
                    }
                }

                //rounded down
                var peak = names.length/3 | 0;
                if(max >= peak && max >= 3 && names.length >= 9){
                    for(var i = 0; i < names.length; i++){
                        names[i] = names[i].replace(maxName, '');
                    }
                    if($('#dynamicmodel-class')[0].value == ''){
                        $('#dynamicmodel-class')[0].value = maxName;
                    }
                }
                names = names.filter(function(e){return e});

                for(var i = 0; i < names.length; i=i+2) {
                    fullnames.push(names[i] + ' ' + names[i+1]);
                }
            }
        }

        fullnames = fullnames.filter( function(value, index, self){
            return self.indexOf(value) === index;
        });

        var list = document.createElement('ul');
        var value = '';

        for(var i = 0; i < fullnames.length; i++) {
            var item = document.createElement('li');
            item.appendChild(document.createTextNode(fullnames[i]));
            list.appendChild(item);
            value += fullnames[i] + '\\n';
        }

        $('#preview')[0].innerHTML = list.outerHTML;
        $('#dynamicmodel-names')[0].value = value;
        $('#dynamicmodel-submit-btn')[0].innerHTML = 'Create ' + fullnames.length + ' Tickets'

        return;
    })

" );

$this->registerJs($js);

$this->title = \Yii::t('tickets', 'Create multiple Tickets');
$this->params['breadcrumbs'][] = ['label' => \Yii::t('tickets', 'Tickets'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ticket-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="alert alert-success" role="alert">
        <span class="glyphicon glyphicon-alert"></span>
        <span>    <?= \Yii::t('tickets', 'For more information, please visit {link}.', [
            'link' => Html::a('Manual / Create multiple tickets', ['/howto/view', 'id' => 'create-multiple-tickets.md'], ['class' => 'alert-link'])
        ]) ?></span>
    </div>



    <div class="ticket-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-md-6">
             <?= $form->field($model, 'exam_id')->widget(Select2::classname(), [
                'data' => [],
                'pluginOptions' => [
                    'dropdownAutoWidth' => true,
                    'width' => 'auto',
                    'allowClear' => true,
                    'placeholder' => '',
                    'ajax' => [
                        'url' => \yii\helpers\Url::to(['exam/index', 'mode' => 'list', 'attr' => 'resultExam']),
                        'dataType' => 'json',
                        'delay' => 250,
                        'cache' => true,
                        'data' => new JsExpression('function(params) {
                            return {
                                q: params.term,
                                page: params.page,
                                per_page: 10
                            };
                        }'),
                        'processResults' => new JsExpression('function(data, page) {
                            return {
                                results: data.results,
                                pagination: {
                                    more: data.results.length === 10 // If there are 10 matches, theres at least another page
                                }
                            };
                        }'),
                    ],
                    'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                    'templateResult' => new JsExpression('function(q) { return q.text; }'),
                    'templateSelection' => new JsExpression('function (q) { return q.text; }'),
                ],
                'options' => [
                    'placeholder' => \Yii::t('tickets', 'Choose an Exam ...')
                ]
            ])->hint(\Yii::t('tickets', 'Choose the exam those tickets has to be assigned to in the list below. Notice, only exams assigned to you will be shown underneath.')); ?>

        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'class')->textInput([
               'placeholder' => \Yii::t('tickets', 'Not yet used...'),
            ])->hint(\Yii::t('tickets', 'This has no function yet.')); ?>
        </div>
    </div>

    <div class="row">
    <div class="col-sm-6">

        <div class="form-group">
            <?= Html::label(\Yii::t('tickets', 'Names')); ?>
            <?= Html::textarea('name', '', [
                'placeholder' => \Yii::t('tickets', 'Please insert a list of names separated by tab, comma, semicolon, newline or all of them combined...'),
                'class' => 'form-control',
                'rows' => '18',
            ]); ?>
            <div class="hint-block"><?= \Yii::t('tickets', 'Student names can be inserted in various different formats. Blocks from an Excel list can just be copied in this field. If you want to combine different formats, use two newlines inbetween them. The preview proposal to the right shows how the names are parsed.') ?></div>
        </div>

        <?= $form->field($model, 'names')->input([
            'placeholder' => \Yii::t('tickets', 'Please insert a list of names separated by tab, comma, semicolon, newline or all of them combined...'),
        ])->hiddenInput()->label(false); ?>

        <div class="form-group">
            <?= Html::submitButton(\Yii::t('tickets', 'Create'), ['class' => 'btn btn-success', 'id' => 'dynamicmodel-submit-btn']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
    <div class="col-sm-6">
        <?= Html::label(\Yii::t('tickets', 'Preview Proposal')); ?>
        <div class="hint-block"><?= \Yii::t('tickets', 'This field shows how the names are parsed.') ?></div>
        <div id="preview"></div>
    </div>
    </div>


    </div>

</div>


</div>
