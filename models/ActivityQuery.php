<?php

namespace app\models;

/**
 * This is the ActiveQuery class for [[Activity]].
 *
 * @see Activity
 */
class ActivityQuery extends TranslatedActivityQuery
{

    /**
     * @return Activity[]|array
     */
    public function own()
    {
        return $this->joinWith(['ticket' => function ($q) {
            $q->joinWith(['exam' => function ($q) {
                $q->andFilterWhere([
                    'exam.user_id' => \Yii::$app->user->id,
                ]);
            }]);
        }]);
    }

    /**
     * @inheritdoc
     * @return Activity[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Activity|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
