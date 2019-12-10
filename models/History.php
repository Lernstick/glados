<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "history".
 *
 * @property integer $id
 * @property string $table
 * @property string $column
 * @property integer $row
 * @property timestamp $changed_at
 * @property integer $changed_by
 * @property string $old_value
 * @property string $new_value
 * @property string $columns
 * @property string $new_values
 * @property string $old_values
 *
 * @property User $user
 * @property string $userName
 */
class History extends \yii\db\ActiveRecord
{

    public $columns_db;
    public $old_values_db;
    public $new_values_db;

    /**
     * \var string separator of the `GROUP_CONCAT()` query
     */
    const SEPARATOR = '::';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'history';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => \Yii::t('history', 'ID'),
            'table' => \Yii::t('history', 'Table'),
            'column' => \Yii::t('history', 'Column'),
            'row' => \Yii::t('history', 'Row'),
            'changed_at' => \Yii::t('history', 'Changed At'),
            'changed_by' => \Yii::t('history', 'Changed By'),
            'old_value' => \Yii::t('history', 'Old Value'),
            'new_value' => \Yii::t('history', 'New Value'),
            'Hash' => \Yii::t('history', 'Hash'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'changed_by']);
    }

    /**
     * Getter for user name
     *
     * @return string The user name or "System" if user id is 0 or "(user removed)"
     * if the user does not exist (anymore).
     */
    public function getUserName()
    {
        if ($this->changed_by == 0) {
            return \Yii::t('history', 'System');
        } else if ($this->changed_by == -1) {
            return \Yii::t('history', 'Client');
        } else if ($this->changed_by == -2) {
            return '<span class="not-set">' . \Yii::t('history', '(unknown)') . '</span>';
        } else if ($this->user == null) {
            return '<span class="not-set">' . \Yii::t('history', '(user removed)') . '</span>';
        } else {
            return $this->user->username;
        }
    }

    /**
     * Getter the new_values array
     *
     * @return array 
     */
    public function getNew_values()
    {
        return explode(History::SEPARATOR, $this->new_values_db);
    }

    /**
     * Getter the old_values array
     *
     * @return array 
     */
    public function getOld_values()
    {
        return explode(History::SEPARATOR, $this->old_values_db);
    }

    /**
     * Getter the columns array
     *
     * @return array 
     */
    public function getColumns()
    {
        return explode(History::SEPARATOR, $this->columns_db);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    private function diff()
    {
        return self::find()
            ->andWhere(['<', 'changed_at', $this->changed_at])
            ->andWhere([
                'table' => $this->table,
                'row' => $this->row
            ])
            ->orderBy(['changed_at' => SORT_DESC]);
    }


    /**
     * @return integer
     */
    public function getDiffToLast()
    {
        $pre = $this->diff()->one();
        if ($pre !== null) {
            return floatval($this->changed_at) - floatval($pre->changed_at);
        } else {
            return -1;
        }
    }

    /**
     * @inheritdoc
     *
     * @return Query the active query used by this AR class.
     */
    public static function find()
    {
        $query = new \yii\db\ActiveQuery(get_called_class());

        $query->select([
            '`history`.*',
            new \yii\db\Expression('
                GROUP_CONCAT(`column` ORDER BY `id` DESC SEPARATOR "' . History::SEPARATOR . '") as `columns_db`,
                GROUP_CONCAT(IFNULL(`new_value`, "") ORDER BY `id` DESC SEPARATOR "' . History::SEPARATOR . '") as `new_values_db`,
                GROUP_CONCAT(IFNULL(`old_value`, "") ORDER BY `id` DESC SEPARATOR "' . History::SEPARATOR . '") as `old_values_db`'),
        ])->groupBy('hash')
        ->orderBy(['changed_at' => SORT_DESC]);

        return $query;
    }

}