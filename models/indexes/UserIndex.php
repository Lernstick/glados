<?php

namespace app\models\indexes;

use Yii;
use app\models\indexes\BaseIndex;

/**
 * Index definitions for the "user" index
 */
class UserIndex extends BaseIndex
{

    /**
     * @inheritdoc
     */
    static public $index = 'user';

	/**
	 * @inheritdoc
	 */
    static public $settings = [
        'analysis' => [
            'analyzer' => [
                'letter' => [
                    'tokenizer' => 'lowercase',
                ],
            ],
        ],
    ];

	/**
	 * @inheritdoc
	 */
    static public $mappings = [
        'properties' => [
            'username'   => [
                'type' => 'text',
                'analyzer' => 'letter',
                'fields' => [
                    'keyword' => ['type' => 'keyword', 'ignore_above' => 256],
                ],
            ],
            'role'       => ['type' => 'text'],
            'type'       => ['type' => 'text'],
            'authMethod' => ['type' => 'text'],
        ],
    ];

    /**
     * @inheritdoc
     */
    static public $autocomplete = [
        'username',
    ];
}
