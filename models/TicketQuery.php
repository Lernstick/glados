<?php

namespace app\models;

/**
 * This is the ActiveQuery class for [[Ticket]].
 *
 * @see Ticket
 */
class TicketQuery extends TranslatedActivityQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * Joins the SQL query with the exam table, that only ticket models associated to
     * the current user are returned
     *
     * @return Ticket[]|array|null
     */
    public function own()
    {
        return $this->joinWith([
            'exam' => function (\yii\db\ActiveQuery $query) {
                $query->where([
                    'exam.user_id' => \Yii::$app->user->id,
                ]);
            }
        ]);
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
