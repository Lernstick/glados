<?php

namespace app\models;

/**
 * This is the ActiveQuery class for [[Ticket]].
 *
 * @see Ticket
 */
class TicketQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @return Ticket[]|array
     */
    public function own()
    {
        return $this->joinWith(['exam' => function ($q) {
            $q->andFilterWhere([
                'exam.user_id' => \Yii::$app->user->id,
            ]);
        }]);
    }

    /**
     * @inheritdoc
     * @return Ticket[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Ticket|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
