<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * This is the model class for the Auth class.
 *
 */
class Auth extends Model
{

    //public $configPath = __DIR__ . '/../config';
    const PATH = __DIR__ . '/../config';

    /* scenario constants */
    const SCENARIO_AD = 'ad';
    const SCENARIO_LDAP = 'ldap';

    public $dirtyAttributes; //TODO
    public $isNewRecord; //TODO

    public $configArray;
    public $id;


    public $name;
    public $domain;
    public $mapping;
    public $ldap_uri;
    public $loginScheme;
    public $bindScheme;
    public $searchFilter;

    private $_fileConfig = null;

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        return [
            self::SCENARIO_AD => ['name', 'class', 'domain', 'mapping'],
            self::SCENARIO_LDAP => ['name', 'class', 'ldap_uri', 'mapping'],
        ];
    }


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['name', 'class', 'domain', 'mapping'], 'required', 'on' => [self::SCENARIO_AD]],
            [['name', 'class', 'ldap_uri', 'mapping'], 'required', 'on' => [self::SCENARIO_LDAP]],
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            'name' => Yii::t('auth', 'Name'),
            'config' => Yii::t('auth', 'Konfiguration'),
        ];
    }

    /**
     *
     */
    public function getFileConfig()
    {
        return require(self::PATH . '/auth.php');
        /*if ($this->_fileConfig === null) {
            $this->_fileConfig = require($this->configPath . '/auth.php');
        }
        return $this->_fileConfig;*/
    }

    /**
     *
     */
    public function setFileConfig($config)
    {
        return;
    }

    public function getConfig()
    {
        return json_encode($this->configArray);
    }

    /**
     *
     * @return void
     */
    public function delete()
    {
        return;
    }

    /**
     *
     * @return void
     */
    public function deleteAll()
    {
        return;
    }

    /**
     *
     * @return void
     */
    public function update()
    {
        return;
    }

    /**
     *
     * @return void
     */
    public function updateAll()
    {
        return;
    }

    /**
     *
     * @return bool
     */
    public function save()
    {
        return;
    }

    /**
     * @return Auth
     */
    public function find()
    {
        return;
    }

    /**
     * 
     * @return Auth[] All Authentication elements
     */
    public function findAll($params = null)
    {
        $models = [];
        foreach ($this->fileConfig as $id => $configArray) {
            //var_dump($configArray);die();
            $model = new Auth();
            $model->configArray = $configArray;
            $model->id = $id;
            $model->name = $configArray['name'];
            $models[] = $model;
        }
        return $models;
    }

    /**
     * @param int id
     * @return Auth Authentication instance matching the id, or null if nothing matches.
     */
    public function findOne($id)
    {
        $configArray = self::getFileConfig();
        if (array_key_exists($id, $configArray)) {
            $model = new Auth();
            $model->configArray = $configArray[$id];
            $model->name = $configArray[$id]['name'];
            $model->id = $id;
            return $model;
        } else {
            return null;
        }
    }

}
