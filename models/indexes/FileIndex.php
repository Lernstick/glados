<?php

namespace app\models\indexes;

use Yii;
use app\models\indexes\BaseIndex;

/**
 * Index definitions for the "file" index
 */
class FileIndex extends BaseIndex
{

    /**
     * @inheritdoc
     */
    static public $index = 'file';

	/**
	 * @inheritdoc
	 */
    static public $settings = [
        "index" => [
            "analysis" => [
               "analyzer" => [
                    "path_analyzer" => [
                        "type" => "custom",
                        "tokenizer" => "path_tokenizer",
                        "filter" => "lowercase"
                    ],
                    'german_path' => [
                        "tokenizer" => "letter",
                        "filter" => [
                            "lowercase",
                            "german_stop",
                            "german_keywords",
                            "german_normalization",
                            "german_stemmer"
                        ]   
                    ],
                    "quoted_path_analyzer" => [
                        "type" => "custom",
                        "tokenizer" => "keyword",
                        "filter" => "lowercase"
                    ]
                ],
                "tokenizer" => [
                    "path_tokenizer" => [
                        "type" => "char_group",
                        "tokenize_on_chars" => ["/", "punctuation"],
                    ]
                ],
                'filter' => [
                    "german_stop" => [
                      "type" =>      "stop",
                      "stopwords" => "_german_"
                    ],
                    "german_keywords" => [
                      "type" =>     "keyword_marker",
                      "keywords" => [] 
                    ],
                    "german_stemmer" => [
                      "type" =>     "stemmer",
                      "language" => "light_german"
                    ]
                ]
            ]
        ]
    ];

	/**
	 * @inheritdoc
	 */
    static public $mappings = [
        'properties' => [
            //'path' => ['type' => 'text', 'analyzer' => 'path_analyzer'],
            'path' => [
                'type' => 'text',
                'analyzer' => 'path_analyzer',
                'search_quote_analyzer' => 'quoted_path_analyzer',
                'fields' => [
                    'raw' => ['type' => 'text', 'analyzer' => 'quoted_path_analyzer'],
                    'de' =>  ['type' => 'text', 'analyzer' => 'german_path'],
                    'en' =>  ['type' => 'text', 'analyzer' => 'english']
                ]
            ],
            'directory' => ['type' => 'text', 'analyzer' => 'path_analyzer'],
            'filename' => [
                'type' => 'text',
                'analyzer' => 'path_analyzer',
                'search_quote_analyzer' => 'quoted_path_analyzer',
                'fields' => [
                    'keyword' => ['type' => 'keyword'],
                    'suggest' => ['type' => 'search_as_you_type'],
                    'raw' => ['type' => 'text', 'analyzer' => 'quoted_path_analyzer'],
                    'de' =>  [
                        'type' => 'text',
                        'analyzer' => 'german_path',
                        'fields' => ['suggest' => ['type' => 'search_as_you_type']],
                    ],
                    'en' =>  [
                        'type' => 'text',
                        'analyzer' => 'english',
                        'fields' => ['suggest' => ['type' => 'search_as_you_type']],
                    ],
                ],
            ],
            'mimetype' => ['type' => 'text'],
            'content'  => ['type' => 'text'],
            'size'     => ['type' => 'integer'],
            'archive'  => ['type' => 'text'],
            'exam'     => ['type' => 'integer'],
            'user'     => ['type' => 'integer'],
        ],
    ];

    /**
     * @inheritdoc
     */
    static public $autocomplete = [
        'filename' => [
            'filename',
            'filename.suggest',
            'filename.suggest._2gram',
            'filename.suggest._3gram',
            'filename.de',
            'filename.de.suggest',
            'filename.de.suggest._2gram',
            'filename.de.suggest._3gram',
            'filename.en',
            'filename.en.suggest',
            'filename.en.suggest._2gram',
            'filename.en.suggest._3gram',
        ]
    ];

}
