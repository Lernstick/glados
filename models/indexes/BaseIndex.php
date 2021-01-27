<?php

namespace app\models\indexes;

use Yii;
use yii\base\Model;

/**
 * TODO
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

}
