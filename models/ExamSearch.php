<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Exam;

/**
 * ExamSearch represents the model behind the search form about `app\models\Exam`.
 */
class ExamSearch extends Exam
{

    public $userName;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'user_id'], 'integer'],
            [['name', 'subject', 'file', 'userName'], 'safe'],
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
        $query = Exam::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination'=>array(
                'pageSize'=>\Yii::$app->params['itemsPerPage'],
            ),
        ]);

        $dataProvider->setSort([
            'attributes' => [
                'name',
                'subject',
                'userName' => [
                    'asc' => ['user.username' => SORT_ASC],
                    'desc' => ['user.username' => SORT_DESC],
                    'label' => 'Owner'
                ]
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
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'subject', $this->subject])
            ->andFilterWhere(['like', 'file', $this->file]);

        // filter by exam name, subject and user_id
        $query->joinWith(['user' => function ($q) {
            $q->andFilterWhere(['like', 'user.username', $this->userName]);
        }]);

        Yii::$app->user->can('exam/index/all') ?: $query->own();

        return $dataProvider;
    }

    public function getTotalExams()
    {

        $query = Exam::find();
        Yii::$app->user->can('exam/index/all') ?: $query->own();
        return $query;

    }

}
