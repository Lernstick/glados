<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use app\models\Ticket;

/**
 * TicketSearch represents the model behind the search form about `app\models\Ticket`.
 */
class TicketSearch extends Ticket
{

    public $examName;
    public $examSubject;
    public $userId;
    public $state;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        unset($this->token);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['exam_id'], 'integer'],
            [['token', 'examSubject', 'examName', 'userId', 'test_taker', 'state'], 'safe'],
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
        $query = Ticket::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => array(
                'pageSize' => \Yii::$app->params['itemsPerPage'],
            ),
        ]);

        $dataProvider->setSort([
            'attributes' => [
                'state',
                'start',
                'end',
                'examName' => [
                    'asc' => ['exam.name' => SORT_ASC],
                    'desc' => ['exam.name' => SORT_DESC],
                    'label' => 'Exam Name'
                ],
                'examSubject' => [
                    'asc' => ['exam.subject' => SORT_ASC],
                    'desc' => ['exam.subject' => SORT_DESC],
                    'label' => 'Exam Subject'
                ],
                'test_taker'
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
            'exam_id' => $this->exam_id,
            'start' => $this->start,
            'end' => $this->end,
        ]);

        $query->andFilterWhere(['like', 'token', $this->token])
            ->andFilterWhere(['like', 'test_taker', $this->test_taker])
            ->andFilterHaving(['state' => $this->state]);

        // filter by exam name, subject and user_id
        $query->joinWith(['exam' => function ($q) {
            $q->andFilterWhere(['like', 'exam.name', $this->examName])
            ->andFilterWhere(['exam.subject' => $this->examSubject]);
        }]);

        Yii::$app->user->can('ticket/index/all') ?: $query->own();

        return $dataProvider;
    }

    public function getExamList()
    {

        $exams = Yii::$app->user->can('ticket/index/all') ? 
            Exam::find()->asArray()->all() : 
            Exam::find()->where(['user_id' => Yii::$app->user->id])->asArray()->all();

        return ArrayHelper::map($exams, 'id', function($exams){
                return $exams['subject'] . ' - ' . $exams['name'];
            }
        );
    }

    public function getSubjectList()
    {
        $exams = Yii::$app->user->can('ticket/index/all') ? 
            Exam::find()->asArray()->all() : 
            Exam::find()->where(['user_id' => Yii::$app->user->id])->asArray()->all();

        return ArrayHelper::map($exams, 'subject', 'subject');
    }


    public function getRunningTickets()
    {

        return $tickets = Ticket::find()
            ->where(['not', ['start' => null]])
            ->andWhere(['end' => null]);

        //return Yii::$app->user->can('ticket/index/all') ? $tickets : $tickets->own();
       
    }

    public function getCompletedTickets()
    {
        return $tickets = Ticket::find()
            ->where(['not', ['start' => null]])
            ->andWhere(['not', ['end' => null]]);

        //return Yii::$app->user->can('ticket/index/all') ? $tickets : $tickets->own();
    }

    public function getTotalTickets()
    {
        return Ticket::find();
        //return Yii::$app->user->can('ticket/index/all') ? $tickets : $tickets->own();
    }

    public function getTotalDuration()
    {

        $query = Ticket::find();

        return $query->sum(
            new \yii\db\Expression(
                'TIMESTAMPDIFF(
                    SECOND,
                    `start`,
                    IF(`end` is null,
                        CURRENT_TIMESTAMP(),
                        `end`
                    )
                )'
            )
        );
    }

}
