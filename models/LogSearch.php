<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ArrayDataProvider;
use yii\data\Sort;

/**
 * LogSearch represents the model behind the search form about `app\models\Log`.
 */
class LogSearch extends Log
{

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type', 'date'], 'safe'],
        ];
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ArrayDataProvider
     */
    public function search($params)
    {
        $sort = new Sort([
            'defaultOrder' => [
                'date' => SORT_DESC,
            ],
            'attributes' => [
                'date',
                'type',
            ],
        ]);

        $dataProvider = new ArrayDataProvider([
            'sort' => $sort,
            'pagination' => [
                'defaultPageSize' => 10,
                'pageSizeLimit' => [1, 100],
            ],
        ]);

        // load the search form data and validate
        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $dataProvider->allModels = Log::findAll($params['LogSearch']);

        return $dataProvider;
    }
}
