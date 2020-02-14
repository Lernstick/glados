<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use app\models\History;

/**
 * HistorySearch represents the model behind the search form about `app\models\History`.
 */
class HistorySearch extends History
{

    public $userName;
    private $_columnList;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['table', 'column', 'row', 'changed_at', 'old_value', 'new_value'], 'safe'],
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
        $query = History::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> [
                'defaultOrder' => ['changed_at'=>SORT_DESC]
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'table' => $this->table,
            'column' => $this->column,
            'row' => $this->row,
            'changed_at' => $this->changed_at,
            'old_value' => $this->old_value,
            'new_value' => $this->new_value,
        ]);

        return $dataProvider;
    }

    /**
     * Generates a list of columns with their translated names to filter the history view
     * @param $model yii\base\Model the data model of the item (for example: app\models\Ticket, app\models\Exam, app\models\User, ...)
     * @return array Array of columns, where the keys are column names from the database
     * and the values are translated column names to display in the view.
     */
    public function getColumnList($model)
    {
        if (empty($this->_columnList)) {
            $query = History::find();
            $query->where(['table' => $model->tableName(), 'row' => $model->id]);

            // filter out all _data fields
            $query->andWhere(['not like', 'column', ['%_data'], false]);
            $query->groupBy('column');
            $items = $query->asArray()->all();

            $this->_columnList = ArrayHelper::map($items, 'column', function($items) use ($model) {
                    $parts = explode('_', $items['column']);
                    $last = array_pop($parts);
                    $pname = implode('_', $parts);
                    if ($last == 'id'
                        && $model->hasMethod('getTranslatedFields')
                        && in_array($pname, $model->translatedFields)
                    ) {
                        return $model->getAttributeLabel($pname);
                    } else {
                        return $model->getAttributeLabel($items['column']);
                    }
                }
            );
        }
        return $this->_columnList;
    }
}
