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

    public $token;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['date', 'description', 'ticket_id', 'token'], 'safe'],
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
            'pagination' => array(
                'pageSize' => 10,
            ),
        ]);

        $dataProvider->setSort([
            'defaultOrder' => ['date' => SORT_DESC],
            'attributes' => [
                'date',
                'description',
                'ticket_id',
                'token' => [
                    'asc' => ['ticket.token' => SORT_ASC],
                    'desc' => ['ticket.token' => SORT_DESC],
                ],
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'ticket_id' => $this->ticket_id
        ]);

        $query->joinWith(['ticket' => function ($q) {
            $q->andFilterWhere(['like', 'ticket.token', $this->token]);
        }]);

        $dateEnd = new \DateTime($this->date);
        $dateEnd->modify('+1 day');
        $query->andFilterWhere(['between', 'date', $this->date, $dateEnd->format('Y-m-d')]);

        $query->andFilterWhere(['like', 'description', $this->description]);

        Yii::$app->user->can('activity/index/all') ?: $query->own();

        return $dataProvider;
    }

    /* TODO: not needed anymore */
    public function getNewActivities()
    {
        $cookies = Yii::$app->request->cookies;
        $lastvisited = $cookies->getValue('lastvisited');
        $date = $lastvisited == null ? 0 : $lastvisited;
        $activities = Activity::find()
            ->where(['>', 'date', $date]);

        return Yii::$app->user->can('activity/index/all') ? $activities : $activities->own();
    }

}
