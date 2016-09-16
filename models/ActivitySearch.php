<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Activity;

/**
 * ActivitySearch represents the model behind the search form about `app\models\Activity`.
 */
class ActivitySearch extends Activity
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['date', 'description', 'ticket_id'], 'safe'],
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
        $query = Activity::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['date' => SORT_DESC]],
            'pagination' => array(
                'pageSize' => 20,
            ),
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'date' => $this->date,
            'ticket_id' => $this->ticket_id
        ]);

        $query->andFilterWhere(['like', 'description', $this->description]);

        Yii::$app->user->can('activity/index/all') ?: $query->own();

        return $dataProvider;
    }

    public function getNewActivities()
    {
        $cookies = Yii::$app->request->cookies;
        $lastvisited = $cookies->getValue('lastvisited');

        return Activity::find()
            ->where(['>', 'date', $lastvisited]);
    }

}
