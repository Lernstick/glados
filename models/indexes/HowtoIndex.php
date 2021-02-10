<?php

namespace app\models\indexes;

use Yii;
use app\models\indexes\BaseIndex;

/**
 * Index definitions for the "howto" index
 */
class HowtoIndex extends BaseIndex
{

    /**
     * @inheritdoc
     */
    static public $index = 'howto';

	/**
	 * @inheritdoc
	 */
    static public $settings = [];

	/**
	 * @inheritdoc
	 */
    static public $mappings = [
        'properties' => [
            'title'      => ['type' => 'text', 'analyzer' => 'english'],
            'content'    => ['type' => 'text', 'analyzer' => 'english'],
        ],
    ];

    /**
     * @inheritdoc
     */
    static public $autocomplete = [
        'title',
    ];
}
