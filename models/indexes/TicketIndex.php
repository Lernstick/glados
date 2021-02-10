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
        'test_taker',
    ];

}
