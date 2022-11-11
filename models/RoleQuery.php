<?php

namespace app\models;

/**
 * This is the ActiveQuery class for [[Role]].
 *
 * @see Role
 */
class RoleQuery extends \yii\db\ActiveQuery
{

    /**
     * @return Role[]|array|null
     */
    public function own()
    {
        return $this;
    }

    /**
     * @inheritdoc
     * @return Role[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Role|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
