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
            'name'       => ['type' => 'text'],
            'subject'    => ['type' => 'text'],
            'fileInfo'   => ['type' => 'text'],
            'file2Info'  => ['type' => 'text'],
            'user'       => ['type' => 'integer'],
        ],
    ];

    /**
     * @inheritdoc
     */
    static public $autocomplete = [
        'name',
        'subject',
    ];

}
