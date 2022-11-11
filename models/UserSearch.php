<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use app\models\User;

/**
 * UserSearch represents the model behind the search form about `app\models\User`.
 */
class UserSearch extends User
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['username', 'roles', 'last_visited', 'type'], 'safe'],
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
        $query = User::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => array(
                'pageSize' => \Yii::$app->params['itemsPerPage'],
            ),            
        ]);

        $dataProvider->setSort([
            'attributes' => [
                'username',
                'roles' => [
                    'asc' => ['auth_assignment.item_name' => SORT_ASC],
                    'desc' => ['auth_assignment.item_name' => SORT_DESC],
                    'label' => 'Owner'
                ],
                'type',
                'last_visited',
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere(['type' => $this->type]);

        $dateEnd = new \DateTime($this->last_visited);
        $dateEnd->modify('+1 day');
        $query->andFilterWhere(['between', 'last_visited', $this->last_visited, $dateEnd->format('Y-m-d')]);

        $query->andFilterWhere(['like', 'username', $this->username]);

        $query->joinWith(['authAssignments' => function ($q) {
            $q->andFilterWhere(['auth_assignment.item_name' => $this->roles]);
        }]);

        // Else users associated to multiple roles will appear multiple times in the index views
        $query->groupBy('id');

        return $dataProvider;
    }

    public function getRoleList()
    {
        $auth = Yii::$app->authManager;
        $roles = $auth->getRoles();
        return ArrayHelper::map($roles, 'name', 'name');
    }

}
