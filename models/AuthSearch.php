<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ArrayDataProvider;
use app\models\Auth;
use app\models\User;

/**
 * AuthSearch represents the model behind the search form about `app\models\Auth`.
 */
class AuthSearch extends Auth
{

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
        $this->load($params);

        $models = Auth::findAll();

        $dataProvider = new ArrayDataProvider([
            'allModels' => $models,
            'pagination' => array(
                'pageSize' => 20,
            ),
        ]);

        return $dataProvider;
    }

    /**
     * Getter for the list of authentication methods and their names
     * For Example:
     * 
     * ```php
     * [
     *      0                                       => "Local (Databse)"
     *      "3df178eb-45b9-479c-b665-a97454706e6b"  => "LDAP (Generic LDAP)"
     *      ...
     *      "f9ca4350-0769-4929-845a-d05c926a3984"  => "AD (Active Directory)"
     * ]
     * ```
     * 
     */
    public function getAuthSelectlist()
    {
        $query = User::find()->select('type')->groupBy("type");
        $command = $query->createCommand();
        $data = $command->queryAll();

        //$cfg = $this->fileConfig;
        $cfg = Auth::getFileConfig();
        array_walk($cfg, function(&$item) {
            $x = new $item['class']($item   );
            $item = $x->name . ' (' . $x->typeName . ')';
            unset($x);
        });

        foreach ($data as $key => $id) {
            if (!array_key_exists($id["type"], $cfg)) {
                $cfg[$id["type"]] = Yii::t('auth', '{type} (No Authentication Method)', [
                    'type' => $id["type"],
                ]);
            }
        }
        return $cfg;
    }

    /**
     * Gets a list of usernames by condition
     * @param $condition
     * For Example:
     * 
     * ```php
     * [
     *      0 => "user_1"
     *      1 => "user_2"
     *      ...
     *      n => "user_n"
     * ]
     * ```
     * 
     */
    public function getUsernameList($condition)
    {
        $query = User::find()->where($condition)->select('username');
        $command = $query->createCommand();
        return array_column($command->queryAll(), 'username');
    }

}
