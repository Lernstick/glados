<?php

namespace app\models\indexes;

use Yii;
use yii\base\Model;

/**
 * Base index definitions
 */
class BaseIndex extends Model
{

    /**
     * @var string name if the index
     */
    static public $index;

    /**
     * @var array the settings for the index
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/index-modules.html#index-modules-settings
     */
    static public $settings = [];

    /**
     * @var array the mappings for the index
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping.html
     */
    static public $mappings = [];

    /**
     * @var array the fields that should be autocompleted while typing. A phrase_prefix search is 
     * performed on these fields. Key-value pairs of basefield and suggest-fields should be given.
     * Each of these field should have a subfield called "field.keyword" to be able to create buckets 
     * in aggregations.
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html#type-phrase
     */
    static public $autocomplete = [];
}
