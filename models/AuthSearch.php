<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ArrayDataProvider;
use app\models\Auth;

/**
 * AuthSearch represents the model behind the search form about `app\models\Auth`.
 */
class AuthSearch extends Auth
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
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ArrayDataProvider
     */
    public function search($params)
    {
        $models = Auth::findAll($params);

        $dataProvider = new ArrayDataProvider([
            'allModels' => $models,
            'pagination' => array(
                'pageSize' => 20,
            ),
        ]);

        return $dataProvider;
    }

}
