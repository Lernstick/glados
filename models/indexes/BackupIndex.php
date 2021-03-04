<?php

namespace app\models\indexes;

use Yii;
use app\models\indexes\BaseIndex;

/**
 * Index definitions for the "backup" index
 */
class BackupIndex extends BaseIndex
{

    /**
     * @inheritdoc
     */
    static public $index = 'backup';

	/**
	 * @inheritdoc
	 */
    static public $settings = [];

	/**
	 * @inheritdoc
	 */
    static public $mappings = [
        'properties' => [
            'date'  => [
                'type' => 'date',
                # yyyy-MM-dd'T'HH:mm:ss.SSSSSSZ or yyyy-MM-dd or timestamp
                # @see https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-date-format.html
                'format' => 'date_optional_time||epoch_millis',
            ],
            'errors'                     => ['type' => 'integer'],
            'elapsedTime'                => ['type' => 'float'],
            'sourceFiles'                => ['type' => 'integer'],
            'mirrorFiles'                => ['type' => 'integer'],
            'deletedFiles'               => ['type' => 'integer'],
            'changedFiles'               => ['type' => 'integer'],
            'incrementFiles'             => ['type' => 'integer'],
            'totalDestinationSizeChange' => ['type' => 'integer'],
            'ticket'                     => ['type' => 'integer'],
        ],
    ];

    /**
     * @inheritdoc
     */
    static public $autocomplete = [];
}
