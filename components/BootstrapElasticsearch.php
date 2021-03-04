<?php

namespace app\components;

use yii\base\BootstrapInterface;

/**
 * BootstrapElasticsearch implements BootstrapInterface.
 *
 * Attaches the component if elasticsearch is enabled in the config
 */
class BootstrapElasticsearch implements BootstrapInterface
{
    public function bootstrap($app)
    {
        if (array_key_exists('elasticsearch', \Yii::$app->params) && \Yii::$app->params['elasticsearch']) {
            $nodes = [];
            foreach (explode(',', \Yii::$app->params['elasticsearchNodes']) as $node) {
                $nodes[] = ['http_address' => $node];
            }
            $app->set('elasticsearch', [
                'class' => 'yii\elasticsearch\Connection',
                'nodes' => $nodes,
                'dslVersion' => 7, // default is 5
            ]);
        }
    }
}