<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use app\models\Ticket;
use yii\db\Expression;


/**
 * TicketSearch represents the model behind the search form about `app\models\Ticket`.
 */
class TicketSearch extends Ticket
{

    public $examName;
    public $examSubject;
    public $userId;
    public $state;
    public $abandoned;

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
            [['token', 'examSubject', 'examName', 'userId', 'test_taker', 'state', 'abandoned'], 'safe'],
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
                'token',
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
                'time_limit',
                'ip',
                'client_state',
                'backup_interval',
                'backup_size',
                'backup_last',
                'backup_last_try',
                'backup_state',
                'restore_state',
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // filter by exam name, subject and user_id
        $query->joinWith(['exam' => function ($q) {
            $q->andFilterWhere(['like', 'exam.name', $this->examName])
            ->andFilterWhere(['exam.subject' => $this->examSubject]);
        }]);

        $at = \Yii::$app->params['abandonTicket'] === null ? 'NULL' : \Yii::$app->params['abandonTicket'];

        if ($this->abandoned == 'Yes' || $this->abandoned == 'No') {
            $query->andFilterHaving(['or',
                ['state' => Ticket::STATE_RUNNING],
                ['state' => Ticket::STATE_CLOSED],
                ['state' => Ticket::STATE_SUBMITTED]
            ])
            ->andFilterWhere(['not', ['ip' => null]])
            ->andFilterWhere(['not', ['backup_interval' => 0]])
            ->andFilterWhere(['last_backup' => 0]);

            $query->andFilterWhere([
                '<',

                # the computed abandoned time (cat). Ticket is abandoned after this amount of seconds
                new Expression('COALESCE(NULLIF(`ticket`.`time_limit`,0),NULLIF(`exam`.`time_limit`,0),ABS(' . $at . '/60),180)*60'),

                # amount of time since last successful backup or since the exam has started and the last backup try or now (nbt)
                new Expression('COALESCE(unix_timestamp(`ticket`.`backup_last_try`), unix_timestamp(NOW())) - COALESCE(unix_timestamp(`ticket`.`backup_last`), unix_timestamp(`ticket`.`start`), 0)')
            ]);

            if ($this->abandoned == 'No') {
                $aband_ids = ArrayHelper::getColumn($query->all(), 'id');

                # invert the query for anadoned=yes
                $query = Ticket::find()->where(['not', ['ticket.id' => $aband_ids]]);
                $dataProvider->query = $query;
            }
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
