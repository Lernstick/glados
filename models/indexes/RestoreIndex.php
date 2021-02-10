<?php

namespace app\models\indexes;

use Yii;
use app\models\indexes\BaseIndex;

/**
 * Index definitions for the "restore" index
 */
class RestoreIndex extends BaseIndex
{

    /**
     * @inheritdoc
     */
    static public $index = 'restore';

	/**
	 * @inheritdoc
	 */
    static public $settings = [];

	/**
	 * @inheritdoc
	 */
    static public $mappings = [
        'properties' => [
            'startedAt'   => [
                'type' => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis'
            ],
            'finishedAt'  => [
                'type' => 'date',
                'format' => 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis'
            ],
            'restoreDate' => [
                'type' => 'date',
                'ignore_malformed' => true,
                # yyyy-MM-dd'T'HH:mm:ss.SSSSSSZ or yyyy-MM-dd or timestamp
                # @see https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-date-format.html
                'format' => 'date_optional_time||epoch_millis',
            ],
            'file'        => ['type' => 'text'],
            'ticket'      => ['type' => 'integer'],
            'exam'        => ['type' => 'integer'],
            'user'        => ['type' => 'integer'],
        ],
    ];

    /**
     * @inheritdoc
     */
    static public $autocomplete = [];
}
