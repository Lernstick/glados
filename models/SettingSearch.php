<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Setting;

/**
 * SettingSearch represents the model behind the search form about `app\models\Setting`.
 */
class SettingSearch extends Setting
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
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
        $query = Setting::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => array(
                'pageSize' => \Yii::$app->params['itemsPerPage'],
            ),            
        ]);

        $dataProvider->setSort([
            'defaultOrder' => ['key' => SORT_DESC],
            'attributes' => [
                'date',
                'key',
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere(['key' => $this->key]);
        $query->andFilterWhere(['value' => $this->value]);

        $dateEnd = new \DateTime($this->date);
        $dateEnd->modify('+1 day');
        $query->andFilterWhere(['between', 'date', $this->date, $dateEnd->format('Y-m-d')]);

        return $dataProvider;
    }
}
