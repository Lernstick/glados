<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use app\models\ExamSetting;

/**
 * This is the base model class for exam_setting_avail tables.
 *
 * @property integer $id
 * @property string $key
 * @property string $value
 * @property string $description
 */
class ExamSettingAvail extends TranslatedActiveRecord
{

    /* db translated fields */
    public $name_db;
    public $name_orig;
    public $description_db;
    public $description_orig;

    public $noPermissionCheck = true;

    /**
     * @inheritdoc
     */
    public function getTranslatedFields()
    {
        return [
            'name',
            'description',
        ];
    }

    /**
     * Lists an attribute
     * @param string attr attribute to list
     * @param string q query
     * @param int page
     * @param int per_page
     * @param int id which attribute should be the id
     * @param bool showQuery whether the query itself should be shown in the 
     *                  output list.
     * @param string the attribute to order by
     *
     * @return array
     */
    public function selectList($attr, $q, $page = 1, $per_page = 10, $id = null, $showQuery = true, $orderBy = null)
    {

        $id = is_null($id) ? $attr : $id;

        $query = $this->find()->where(['belongs_to' => null]);

        if (!is_null($q) && $q != '') {
            $query->having(['like', $attr, $q]);
        }

        is_null($orderBy) ? $query->orderBy($attr) : $query->orderBy($orderBy);

        $query->groupBy($attr); // distincts even a calculated field

        $out = ['results' => []];
        if ($showQuery === true && $page == 1 && $q != null) {
            $out = ['results' => [
                0 => ['id' => $q, 'text' => $q == null ? $q : \Yii::t('form', '<i>Search for... </i><b>{query}</b>', ['query' => $q])]
            ]];
            $per_page -= 1;
        }

        $command = $query->limit($per_page)->offset(($page-1)*$per_page)->createCommand();
        $data = $command->queryAll();

        foreach ($data as $key => $value) {
            $out['results'][] = [
                'id' => $value[$id],
                // highlight the matching part
                'text' => $q == null ?
                    $value[$attr] :
                    preg_replace('/'.$q.'/i', '<b>$0</b>', $value[$attr]),
                'hint' => $value['description'],
                'type' => $value['type'],
            ];
        }
        return $out;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBelongsTo()
    {
        return $this->hasOne(ExamSettingAvail::className(), ['id' => 'belongs_to']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMembers()
    {
        return $this->hasMany(ExamSettingAvail::className(), ['belongs_to' => 'id']);
    }

    /** 
     * @inheritdoc 
     * @return ActivityQuery the active query used by this AR class. 
     */ 
    public static function find() 
    { 
        return new TranslatedActivityQuery(get_called_class());
    }

}