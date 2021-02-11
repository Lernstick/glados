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
                    'suggest' => ['type' => 'search_as_you_type'],
                    'de' =>  [
                        'type' => 'text',
                        'analyzer' => 'german',
                        'fields' => ['suggest' => ['type' => 'search_as_you_type']],
                    ],
                    'en' =>  [
                        'type' => 'text',
                        'analyzer' => 'english',
                        'fields' => ['suggest' => ['type' => 'search_as_you_type']],
                    ],
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
        'username' => [
            'username',
            'username.suggest',
            'username.suggest._2gram',
            'username.suggest._3gram',
            'username.de',
            'username.de.suggest',
            'username.de.suggest._2gram',
            'username.de.suggest._3gram',
            'username.en',
            'username.en.suggest',
            'username.en.suggest._2gram',
            'username.en.suggest._3gram',
        ]
    ];
}
