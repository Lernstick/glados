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
            'title' => [
                'type' => 'text',
                'analyzer' => 'english',
                'fields' => [
                    'keyword' => ['type' => 'keyword'],
                    'suggest' => ['type' => 'search_as_you_type'],
                ],
            ],
            'content'    => ['type' => 'text', 'analyzer' => 'english'],
        ],
    ];

    /**
     * @inheritdoc
     */
    static public $autocomplete = [
        'title' => [
            'title',
            'title.suggest',
            'title.suggest._2gram',
            'title.suggest._3gram',
        ],
    ];
}
