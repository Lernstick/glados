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
 * @property integer $type
 * @property string $columns
 * @property string $new_values
 * @property string $old_values
 * @property string $types
 *
 * @property User $user
 * @property string $userName
 */
class History extends \yii\db\ActiveRecord
{

    public $columns_db;
    public $old_values_db;
    public $new_values_db;
    public $types_db;

    /**
     * @const string separator of the `GROUP_CONCAT()` query
     */
    const SEPARATOR = '::';

    /* history type constants */
    const TYPE_UPDATE = 0;
    const TYPE_INSERT = 1;
    const TYPE_DELETE = 2;

    /**
     * @var string the filter for the column in the form 
     */
    public $searchColumn = null;

    private $_diffToLast;

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
            'hash' => \Yii::t('history', 'Hash'),
            'type' => \Yii::t('history', 'Type'),
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
     * Getter the types array
     *
     * @return array 
     */
    public function getTypes()
    {
        return array_map('intval', explode(History::SEPARATOR, $this->types_db));
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    private function diff()
    {
        $query = self::find()
            ->andWhere(['<', 'changed_at', $this->changed_at])
            ->andWhere([
                'table' => $this->table,
                'row' => $this->row,
            ])
            ->orderBy(['changed_at' => SORT_DESC]);
        if (!empty($this->searchColumn)) {
            $query = $query->andWhere(['column' => $this->searchColumn]);
        }
        return $query;
    }


    /**
     * @return integer
     */
    public function getDiffToLast()
    {
        if ($this->_diffToLast === null) {
            $pre = $this->diff()->one();
            if ($pre !== null) {
                $this->_diffToLast = floatval($this->changed_at) - floatval($pre->changed_at);
            } else {
                $this->_diffToLast = -1;
            }
        }
        return $this->_diffToLast;
    }

    /**
     * @inheritdoc
     *
     * @return Query the active query used by this AR class.
     */
    public static function find()
    {
        # set the new length of group concat to be 3 times the length of text datatype 
        Yii::$app->db->createCommand('SET SESSION group_concat_max_len = 3*65536')->execute();

        $query = new \yii\db\ActiveQuery(get_called_class());

        $query->select([
            '`history`.*',
            new \yii\db\Expression('
                GROUP_CONCAT(`column` ORDER BY `id` DESC SEPARATOR "' . History::SEPARATOR . '") as `columns_db`,
                GROUP_CONCAT(IFNULL(`new_value`, "") ORDER BY `id` DESC SEPARATOR "' . History::SEPARATOR . '") as `new_values_db`,
                GROUP_CONCAT(IFNULL(`old_value`, "") ORDER BY `id` DESC SEPARATOR "' . History::SEPARATOR . '") as `old_values_db`,
                GROUP_CONCAT(IFNULL(`type`, "") ORDER BY `id` DESC SEPARATOR "' . History::SEPARATOR . '") as `types_db`'),
        ])->groupBy('hash')
        ->orderBy(['changed_at' => SORT_DESC]);

        return $query;
    }

}
