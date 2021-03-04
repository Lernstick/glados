<?php

namespace app\models\indexes;

use Yii;
use app\models\indexes\BaseIndex;

/**
 * Index definitions for the "ticket" index
 */
class TicketIndex extends BaseIndex
{

    /**
     * @inheritdoc
     */
    static public $index = 'ticket';

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
            'start'      => [
                'type' => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis',
            ],
            'end'        => [
                'type' => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis',
            ],
            'token'      => ['type' => 'text'],
            'ip'         => ['type' => 'ip'],
            'test_taker' => [
                'type' => 'text',
                'fields' => [
                    'keyword' => ['type' => 'keyword'],
                    'suggest' => ['type' => 'search_as_you_type'],
                    'de' =>  [
                        'type' => 'text',
                        'analyzer' => 'german',
                        'fields' => ['suggest' => ['type' => 'search_as_you_type']]
                    ],
                    'en' =>  [
                        'type' => 'text',
                        'analyzer' => 'english',
                        'fields' => ['suggest' => ['type' => 'search_as_you_type']]
                    ],
                ],
            ],
            'exam'       => ['type' => 'integer'],
            'user'       => ['type' => 'integer'],
            'state'      => ['type' => 'integer'],
            'keylogger'  => ['type' => 'text'],
        ],
    ];

    /**
     * @inheritdoc
     */
    static public $autocomplete = [
        'test_taker' => [
            'test_taker',
            'test_taker.suggest',
            'test_taker.suggest._2gram',
            'test_taker.suggest._3gram',
            'test_taker.de',
            'test_taker.de.suggest',
            'test_taker.de.suggest._2gram',
            'test_taker.de.suggest._3gram',
            'test_taker.en',
            'test_taker.en.suggest',
            'test_taker.en.suggest._2gram',
            'test_taker.en.suggest._3gram',
        ],
    ];

}
