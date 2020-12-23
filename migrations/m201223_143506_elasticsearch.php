<?php

use yii\db\Migration;
use app\models\Setting;

/**
 * Class m201223_143506_elasticsearch
 */
class m201223_143506_elasticsearch extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // create elasticsearch
        $agent = new Setting([
            'key' => 'elasticsearch',
            'name' => yiit('setting', 'Enable Elasticsearch'),
            'type' => 'boolean',
            'default_value' => false,
            'description' => yiit('setting', 'Enable data indexing with <a target="_blank" href="https://www.elastic.co/">Elasticsearch</a> providing powerful search possibilities.'),
        ]);
        $agent->save(false);

        // create elasticsearchNodes
        $agent = new Setting([
            'key' => 'elasticsearchNodes',
            'name' => yiit('setting', 'List of Elasticsearch nodes'),
            'type' => 'string',
            'default_value' => 'localhost:9200',
            'description' => yiit('setting', 'A list of hosts and ports separated by colon (,) that serve as elasticsearch nodes. Example: <code>localhost:9200,1.2.3.4:9000,elastic.host.tld:9200</code>'),
        ]);
        $agent->save(false);

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        Setting::deleteAll(['key' => 'elasticsearch']);
        Setting::deleteAll(['key' => 'elasticsearchNodes']);
    }
}
