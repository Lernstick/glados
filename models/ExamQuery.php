<?php

namespace app\models;

/**
 * This is the ActiveQuery class for [[Exam]].
 *
 * @see Exam
 */
class ExamQuery extends \yii\db\ActiveQuery
{

    /**
     * Additional condition to find only exams associated to the current user
     *    
     * @return Exam[]|array|null
     */
    public function own()
    {
        return $this->andWhere(['user_id' => \Yii::$app->user->id]);
    }

    /**
     * @inheritdoc
     * @return Exam[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Exam|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
