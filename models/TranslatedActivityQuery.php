<?php

namespace app\models;

/**
 * This is the ActiveQuery class for translated fields in ActiveRecord's
 *
 */
class TranslatedActivityQuery extends \yii\db\ActiveQuery
{

    /**
     * @inheritdoc
     */
    public function init()
    {
        $class = $this->modelClass;
        $c = \Yii::$app->language;
        
        $this->joinWith($class::joinTranslationTables());

        $select = array_map(function($value) use ($c) {
            // first the end-user language, then english (en) as fallback
            return new \yii\db\Expression('COALESCE(
                NULLIF(`' . $value . '`.`' . $c . '`, ""),
                NULLIF(`' . $value . '`.`en`, ""),
                "") as ' . $value);
        }, $class::getTranslatedFields());
        $select = array_merge(['`' . $class::tableName() . '`.*'], $select);

        return $this->addSelect($select);
    }

}
