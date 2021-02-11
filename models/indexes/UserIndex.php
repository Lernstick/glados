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
        "index" => [
            'analysis' => [
                'analyzer' => [
                    'letter' => [
                        'tokenizer' => 'lowercase',
                    ],
                    "username_analyzer" => [
                        "type" => "custom",
                        "tokenizer" => "username_tokenizer",
                        "filter" => "lowercase"
                    ],
                    'german_username' => [
                        "tokenizer" => "letter",
                        "filter" => [
                            "lowercase",
                            "german_stop",
                            "german_keywords",
                            "german_normalization",
                            "german_stemmer"
                        ]
                    ],
                    "quoted_username_analyzer" => [
                        "type" => "custom",
                        "tokenizer" => "keyword",
                        "filter" => "lowercase"
                    ]
                ],
                "tokenizer" => [
                    "username_tokenizer" => [
                        "type" => "char_group",
                        "tokenize_on_chars" => ["@", "punctuation"],
                    ]
                ],
                'filter' => [
                    "german_stop" => [
                      "type" => "stop",
                      "stopwords" => "_german_"
                    ],
                    "german_keywords" => [
                      "type" => "keyword_marker",
                      "keywords" => []
                    ],
                    "german_stemmer" => [
                      "type" => "stemmer",
                      "language" => "light_german"
                    ]
                ]
            ],
        ],
    ];

	/**
	 * @inheritdoc
	 */
    static public $mappings = [
        'properties' => [
            'username' => [
                'type' => 'text',
                'analyzer' => 'username_analyzer',
                'search_quote_analyzer' => 'quoted_username_analyzer',
                'fields' => [
                    'letter' => ['type' => 'text', 'analyzer' => 'letter'],
                    'keyword' => ['type' => 'keyword'],
                    'suggest' => ['type' => 'search_as_you_type'],
                    'raw' => ['type' => 'text', 'analyzer' => 'quoted_username_analyzer'],
                    'de' =>  [
                        'type' => 'text',
                        'analyzer' => 'german_username',
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
