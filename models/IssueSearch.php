<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Issue;

/**
 * IssueSearch represents the model behind the search form about `app\models\Issue`.
 */
class IssueSearch extends Issue
{

    /**
     * @var integer exam id
     */
    public $exam_id;

    /**
     * @var bool
     */
    public $solved;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'ticket_id', 'exam_id'], 'integer'],
            [['occuredAt', 'solved', 'key', 'exam_id'], 'safe'],
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
        $query = Issue::find();


        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> [
                'defaultOrder' => ['occuredAt' => SORT_DESC]
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        if ($this->solved === true) {
            $query->andWhere(['not', ['solvedAt' => null]]);
        } else if ($this->solved === false) {
            $query->andWhere(['solvedAt' => null]);
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'key' => $this->key,
            'ticket_id' => $this->ticket_id,
        ]);

        $query->joinWith($this->joinTables());
        $query->andFilterWhere(['like', 'ticket.exam_id', $this->exam_id]);

        return $dataProvider;
    }
}
