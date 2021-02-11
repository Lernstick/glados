<?php

namespace app\models\indexes;

use Yii;
use app\models\indexes\BaseIndex;
use app\components\ElasticsearchBehavior;

/**
 * Index definitions for the "meta" index
 */
class MetaIndex extends BaseIndex
{
    public $id;
    public $fieldname;
    public $indexname;

    static public $indexes = [
        'user' => '\app\models\indexes\UserIndex',
        'exam' => '\app\models\indexes\ExamIndex',
        'ticket' => '\app\models\indexes\TicketIndex',
        'backup' => '\app\models\indexes\BackupIndex',
        'restore' => '\app\models\indexes\RestoreIndex',
        'howto' => '\app\models\indexes\HowtoIndex',
        'log' => '\app\models\indexes\LogIndex',
        'file'   => '\app\models\indexes\FileIndex',
    ];

    /**
     * @inheritdoc
     */
    static public $index = 'meta';

	/**
	 * @inheritdoc
	 */
    static public $settings = [];

	/**
	 * @inheritdoc
	 */
    static public $mappings = [
        'properties' => [
            'fieldname' => [
                'type' => 'text',
                'fields' => [
                    'keyword' => ['type' => 'keyword'],
                    'suggest' => ['type' => 'search_as_you_type'],
                ],
            ],
            'index'     => ['type' => 'keyword'],
        ],
    ];

    /**
     * @inheritdoc
     */
    static public $autocomplete = [
        'fieldname' => [
            'fieldname',
            'fieldname.suggest',
            'fieldname.suggest._2gram',
            'fieldname.suggest._3gram',
        ],
    ];

    /**
     * @inheritdoc 
     */
    public function behaviors()
    {
        return [
            'ElasticsearchBehavior' => [
                'class' => ElasticsearchBehavior::className(),
                'index' => ['class' => '\app\models\indexes\MetaIndex'],
                'allModels' => function($class) { return $class::getAllModels(); },
                'fields' => [
                    'fieldname',
                    'indexname',
                ],
            ],
        ];
    }

    /**
     * @inheritdoc 
     */
    public static function getAllModels()
    {
        $ret = [];
        $id = 0;

        foreach (self::$indexes as $indexname => $class) {
            $fields = array_keys($class::$mappings['properties']);
            foreach ($fields as $fieldname) {
                $ret[] = new MetaIndex([
                    'id' => $id,
                    'fieldname' => $fieldname,
                    'indexname' => $indexname,
                ]);
                $id++;
            }
        }
        return $ret;
    }

}
