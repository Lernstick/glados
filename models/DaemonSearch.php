<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use app\models\Daemon;

/**
 * DaemonSearch represents the model behind the search form about `app\models\Daemon`.
 */
class DaemonSearch extends Daemon
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'pid'], 'integer'],
            [['state', 'description', 'started_at'], 'safe'],
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
     * @return ArrayDataProvider
     */
    public function search($params)
    {
        $query = Daemon::find();
        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => array(
                'pageSize' => \Yii::$app->params['maxDaemons'],
            ),
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
            'pid' => $this->pid,
            'started_at' => $this->started_at,
        ]);

        $query->andFilterWhere(['like', 'state', $this->state])
            ->andFilterWhere(['like', 'description', $this->description]);

        //populate the models
        $models = $dataProvider->models;
        foreach($models as $model){
            if($model->running != true && ($key = array_search($model, $models)) !== false){
                //$model->delete();
                unset($models[$key]);
                continue;
            }
        }

        //create new dataProvider with the models in running state
        return new ArrayDataProvider([
            'key' => Daemon::primaryKey()[0],
            'allModels' => $models,
            'pagination' => array(
                'pageSize' => \Yii::$app->params['maxDaemons'],
            ),
        ]);
    }

    //TODO: kommt weg
    public function virtualAttributeSearch($populatedParams, $params = [])
    {
        $models = $this->search($params)->models;
        foreach($models as $model){
            foreach($populatedParams as $attribute => $value){
                if($model->$attribute != $value && ($key = array_search($model, $models)) !== false){
                    unset($models[$key]);
                    continue;
                }
            }
        }
        return new ArrayDataProvider([
            'allModels' => $models
        ]);
        return $models;

    }

}
