<?php

namespace app\models\indexes;

use Yii;
use app\models\indexes\BaseIndex;

/**
 * Index definitions for the "log" index
 */
class LogIndex extends BaseIndex
{

    /**
     * @inheritdoc
     */
    static public $index = 'log';

	/**
	 * @inheritdoc
	 */
    static public $settings = [];

	/**
	 * @inheritdoc
	 */
    static public $mappings = [
        'properties' => [
            'logentries' => ['type' => 'text'],
            'type'       => ['type' => 'text'],
            'restore'    => ['type' => 'text'],
            'backup'     => ['type' => 'text'],
            'ticket'     => ['type' => 'integer'],
        ],
    ];

    /**
     * @inheritdoc
     */
    static public $autocomplete = [];
}
