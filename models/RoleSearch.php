<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Role;

/**
 * RoleSearch represents the model behind the search form about `app\models\Role`.
 */
class RoleSearch extends Role
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'description', 'updated_at', 'created_at'], 'safe'],
        ];
    }

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
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Role::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => array(
                'pageSize' => \Yii::$app->params['itemsPerPage'],
            ),            
        ]);

        $dataProvider->setSort([
            'defaultOrder' => [
                'created_at' => SORT_DESC,
            ],
            'attributes' => [
                'name',
                'description',
                'updated_at',
                'created_at',
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->where(['type' => Role::TYPE]);

        // grid filtering conditions
        $query->andFilterWhere(['like', 'name', $this->name]);
        
        $query->andFilterWhere(['like', 'description', $this->description]);

        if (!empty($this->created_at)) {
            $dateStart = new \DateTime($this->created_at);
            $dateEnd = new \DateTime($this->created_at);
            $dateEnd->modify('+1 day');
            $query->andFilterWhere(['between', 'created_at', $dateStart->format('U'), $dateEnd->format('U')]);
        }

        if (!empty($this->updated_at)) {
            $dateStart = new \DateTime($this->updated_at);
            $dateEnd = new \DateTime($this->updated_at);
            $dateEnd->modify('+1 day');
            $query->andFilterWhere(['between', 'updated_at', $dateStart->format('U'), $dateEnd->format('U')]);
        }

        return $dataProvider;
    }

}
