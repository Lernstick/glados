<?php

namespace app\models\indexes;

use Yii;
use app\models\indexes\BaseIndex;

/**
 * Index definitions for the "exam" index
 */
class ExamIndex extends BaseIndex
{

    /**
     * @inheritdoc
     */
    static public $index = 'exam';

	/**
	 * @inheritdoc
	 */
    static public $settings = [];

	/**
	 * @inheritdoc
	 */
    static public $mappings = [
        'properties' => [
            'createdAt'  => [
                'type' => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis',
            ],
            'name' => [
                'type' => 'text',
                'fields' => [
                    'keyword' => ['type' => 'keyword'],
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
            'subject' => [
                'type' => 'text',
                'fields' => [
                    'keyword' => ['type' => 'keyword'],
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
            'fileInfo'   => ['type' => 'text'],
            'file2Info'  => ['type' => 'text'],
            'user'       => ['type' => 'integer'],
        ],
    ];

    /**
     * @inheritdoc
     */
    static public $autocomplete = [
        'name' => [
            'name',
            'name.suggest',
            'name.suggest._2gram',
            'name.suggest._3gram',
            'name.de',
            'name.de.suggest',
            'name.de.suggest._2gram',
            'name.de.suggest._3gram',
            'name.en',
            'name.en.suggest',
            'name.en.suggest._2gram',
            'name.en.suggest._3gram',
        ],
        'subject' => [
            'subject',
            'subject.suggest',
            'subject.suggest._2gram',
            'subject.suggest._3gram',
            'subject.de',
            'subject.de.suggest',
            'subject.de.suggest._2gram',
            'subject.de.suggest._3gram',
            'subject.en',
            'subject.en.suggest',
            'subject.en.suggest._2gram',
            'subject.en.suggest._3gram',
        ],
    ];

}
