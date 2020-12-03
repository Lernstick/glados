<?php

namespace app\models\elasticsearch;

use Yii;
use yii\elasticsearch\ActiveRecord;
use app\models\Ticket;

/**
 * This is the ticket model class for elasticsearch.
 */
class Test extends \yii\elasticsearch\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return \app\models\Ticket::tableName();
    }

    public function attributes()
    {
        return ['test_taker'];
    }

    /**
     * @return array This model's mapping
     */
    public static function mapping()
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