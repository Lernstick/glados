<?php

namespace app\components;

use yii\widgets\Pjax;
use yii\helpers\Html;
use yii\helpers\Url;

class Editable extends Pjax
{

     /**
     * @var string the content between the opening and closing tag.
     */
    public $content;

     /**
     * @var string URL to load the edit form.
     */
    public $editUrl;

    private $uuid;
    

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->uuid = generate_uuid();
        $this->enablePushState = false;
        $this->options['data-pjax-container'] = true;
    }


     /**
     * @return string content in the popover to be shown, when the form is loading.
     */
    public function getLoading()
    {
        return '<span>' . \Yii::t('app', 'Loading...') . '</span>';
    }

    /**
     * @inheritdoc
     */
    public function run()
    {


        echo '<span class="editable" role="button" id="' . $this->uuid . '">' . $this->content . '</span>';

        $register = "
            $('#" . $this->uuid . "').popover({
                'html': true,
                'placement': 'bottom',
                'content': function(){
                    var div_id =  'tmp-id-' + $.now();
                    $.ajax({
                        url: '" . Url::to($this->editUrl) . "',
                        success: function(response){
                            $('#'+div_id).html(response);
                        }
                    });
                    return '<div id=\"'+ div_id +'\">" . $this->loading . "</div>';
                }
            });
        ";
        $this->view->registerJs($register, \yii\web\View::POS_READY);

        return parent::run();
    }

}
