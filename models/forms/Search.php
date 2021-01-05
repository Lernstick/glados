<?php

namespace app\models\forms;

use Yii;
use yii\elasticsearch\Query;
use yii\elasticsearch\ActiveRecord;
use yii\elasticsearch\ActiveDataProvider;

/**
 * This is the form model class for search queries.
 *
 * @inheritdoc
 *
 */
class Search extends \yii\elasticsearch\ActiveRecord
{

    /**
     * @var string the search query.
     */
    public $q;

    /**
     * @var array the indexes to search, defaults to empty, which means all.
     */
    public $index;

    /**
     * @var array all indexes
     */
    public $indexes = ['user', 'exam', 'ticket', 'backup', 'restore', 'howto', 'log', 'file'];

    /**
     * @inheritdoc
     */
    public function formName()
    {
        return '';
    }

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
            ['q', 'filter', 'filter' => 'trim', 'skipOnArray' => true],
            ['index', 'default', 'value' => null],
            ['index', 'each', 'rule' => ['in', 'range' => $this->indexes, 'skipOnEmpty' => false]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'q' => \Yii::t('search', 'Search query'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'q' => \Yii::t('search', 'todo'),
        ];
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return yii\elasticsearch\ActiveDataProvider
     */
    public function search($params)
    {
        $this->load($params, '');

        $query = new Query;
        $query->from($this->index) # null means all indices are being queried
            ->limit(10);

        if (strpos($this->q, ':') !== false) {
            $query->query(['query_string' => [
                'query' => $this->q,
            ]]);
        } else {
            $query->query(['multi_match' => [
                'query' => $this->q,
                'fuzziness' => 'AUTO',
                'fields' => ['*']
            ]]);
        }

        $query->highlight(['fields' =>  ['*' => [ "pre_tags" => ["<em>"], "post_tags" => ["</em>"] ]]]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [ 'pageSize' => 10, ]
        ]);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // @todo only query own elements
        //Yii::$app->user->can('ticket/index/all') ?: $query->own();

        return $dataProvider;
    }

}
