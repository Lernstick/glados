<?php

namespace app\models\forms;

use Yii;
use yii\elasticsearch\ActiveRecord;

/**
 * This is the form model class for elasticsearch queries.
 *
 * @inheritdoc
 *
 */
class ElasticsearchQuery extends \yii\elasticsearch\ActiveRecord
{

    public $method;
    public $url;
    public $data;

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['method'], 'required'],
            [['method', 'url'], 'filter', 'filter' => 'trim', 'skipOnArray' => true],
            [['url', 'data'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'method' => 'Can be either <code>get</code>, <code>head</code>, <code>post</code>, <code>put</code> or <code>delete</code>.',
            'url' => 'Examples: <ul><li><code>ticket/_doc/2964</code> to query id <code>2964</code> from index <code>ticket</code></li><li><code>ticket/_search/?q=hans</code> to search index <code>ticket</code></li><li><code>_cat/indices</code> to print information about all indcies</li></ul>',
            'data' => 'the <code>-d</code> parmeter when calling <code>curl</code> directly. Example: <pre>{
  "query": {
    "fuzzy": {
      "test_taker": {
        "value": "hans"
      }
    }
  }
}</pre>
',
        ];
    }
}
