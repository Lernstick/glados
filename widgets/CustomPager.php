<?php

namespace app\widgets;

use Yii;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\bootstrap\Nav;
use yii\bootstrap\ButtonDropdown;

/**
 * CustomPager enhances [[LinkPager]] with a dropdown list of choosable page-sizes.
 *
 * @inheritdoc
 */
class CustomPager extends \yii\widgets\LinkPager
{

     /**
     * @var string layout of the pager. "LinkPager" is replaces by the buttons rendered 
     * by LinkPager and "CustomPager" is replaced by the rendered dropdown list of page-sizes.
     */
    public $layout = '<div class="row">{LinkPager} {CustomPager}</div>';

     /**
     * @var string text for the add-in text in front of the page-size dropdown, null indicates
     * no add-in text.
     */
    public $addTextPre;

     /**
     * @var string text for the add-in text after the page-size dropdown, null indicates
     * no add-in text.
     */
    public $addTextPost;

     /**
     * @var string layout for the selected element
     */
    public $selectedLayout = '{selected}';

     /**
     * @var array a list of page-sizes and their names appearing in the dropdown list.
     * Keys are page-sizes, values are the corresponding labels.
     */
    public $pageSizeList = [
        5  => 5,
        10 => 10,
        20 => 20,
        30 => 30,
        40 => 40,
        50 => 50,
        100 => 100,
    ];

     /**
     * @var array the HTML options that will passed to the [[yii\bootstrap\Dropdown]] widget.
     */
    public $dropDownOptions = [];

     /**
     * @var array The HTML attributes for the widget container tag.
     */
    public $dropDownNavOptions = [
        'class' => 'pagination pagination-page-size',
    ];

     /**
     * @var array the HTML attributes of the dropdown item's link.
     */
    public $dropDownLinkOptions = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        // catch the output of LinkPager
        ob_start();
        parent::run();
        $content = ob_get_clean();

        echo substitute($this->layout, [
            'LinkPager' => $content,
            'CustomPager' => $this->renderPageSize(),
        ]);

    }

    /**
     * Renders the dropdown list.
     * @return string the rendering result
     */
    private function renderPageSize()
    {
        $items = [];
        foreach($this->pageSizeList as $key => $value) {
            $items[] = [
                'label' => $value,
                'url' => $this->pagination->createUrl($this->pagination->page, $key),
                'active' => $this->pagination->pageSize == $key,
            ];
        }

        $current = ArrayHelper::getValue(
            $this->pageSizeList,
            $this->pagination->pageSize,
            $this->pagination->defaultPageSize
        );

        $navItems = [
            [
                'label' => substitute($this->selectedLayout, ['selected' => $current]),
                'items' => $items,
                'linkOptions' => $this->dropDownLinkOptions,
                'dropDownOptions' => $this->dropDownOptions,
                'encode' => false,
            ],
        ];

        if ($this->addTextPre !== null) {
            array_unshift($navItems, Html::tag('li', Html::tag('span', $this->addTextPre), ['class' => 'text']));
        }
        if ($this->addTextPost !== null) {
            array_push($navItems, Html::tag('li', Html::tag('span', $this->addTextPost), ['class' => 'text']));
        }

        return Nav::widget([
            'items' => $navItems,
            'options' => $this->dropDownNavOptions,
        ]);
    }
}
