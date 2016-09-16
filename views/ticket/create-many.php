<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\web\JsExpression;


/* @var $this yii\web\View */
/* @var $model app\models\Ticket */
/* @var $form yii\widgets\ActiveForm */

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

$this->title = 'Create Tickets';
$this->params['breadcrumbs'][] = ['label' => 'Tickets', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ticket-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="ticket-form">

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'class')->textInput([
            'placeholder' => 'Not yet used...',
        ]); ?>

    <div class="row">
    <div class="col-sm-6">


        <?= Html::label('Names'); ?>
        <?= Html::textarea('name', '', [
            'placeholder' => 'Please insert a list of names separated by tab, comma, semicolon, newline or all of them combined...',
            'class' => 'form-control',
            'rows' => '18',
        ]); ?>

        <?= $form->field($model, 'names')->input([
            'placeholder' => 'Please insert a list of names separated by tab, comma, semicolon, newline or all of them combined...',
        ])->hiddenInput()->label(false); ?>

        <div class="form-group">
            <?= Html::submitButton('Create', ['class' => 'btn btn-success', 'id' => 'dynamicmodel-submit-btn']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
    <div class="col-sm-6">
        <?= Html::label('Preview Proposal'); ?>
        <div id="preview"></div>
    </div>
    </div>


    </div>

</div>


</div>
