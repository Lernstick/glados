<?php

namespace app\models\elasticsearch;

use Yii;
use yii\elasticsearch\ActiveRecord;

/**
 * This is the ticket model class for elasticsearch.
 */
class Ticket extends \yii\elasticsearch\ActiveRecord
{
    public $_attributes = [];

    public function attributes()
    {
        return $this->_attributes;
    }

    /**
     * @inheritdoc
     */
    public static function index()
    {
        return 'ticket';
    }

    /**
     * @return array This model's mapping
     */
    public function mapping()
    {
        return [
            // Field types: https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping.html#field-datatypes
            'properties' => [            
                'test_taker'     => ['type' => 'text'],
                'createdAt'      => ['type' => 'date'],
                'start'          => ['type' => 'date'],
                'end'            => ['type' => 'date'],
            ],
        ];
    }

    /**
     * Set (update) mappings for this model
     */
    public static function updateMapping()
    {
        $db = static::getDb();
        $command = $db->createCommand();
        $command->setMapping(static::index(), static::type(), static::mapping());
    }

    /**
     * Create this model's index
     */
    public static function createIndex()
    {
        $db = static::getDb();
        $command = $db->createCommand();
        $command->createIndex(static::index(), [
            //'aliases' => [ /* ... */ ],
            'mappings' => static::mapping(),
            //'settings' => [ /* ... */ ],
        ]);
    }

    /**
     * Delete this model's index
     */
    public static function deleteIndex()
    {
        $db = static::getDb();
        $command = $db->createCommand();
        $command->deleteIndex(static::index(), static::type());
    }
}