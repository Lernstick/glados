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

    public $configPath = __DIR__ . '/../config';

    public $dirtyAttributes;
    public $isNewRecord;


    public $class;
    public $config;
    public $name;
    public $id;

    private $_fileConfig = null;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['name', 'class', 'config'], 'required'],
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
        if ($this->_fileConfig === null) {
            $this->_fileConfig = require($this->configPath . '/auth.php');
        }
        return $this->_fileConfig;
    }

    /**
     *
     */
    public function setFileConfig($config)
    {
        return;
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
    public function findAll($params)
    {
        $models = [];
        foreach ($this->fileConfig as $id => $config) {
            //var_dump($config);die();
            $model = new Auth();
            $model->config = $config;
            $model->id = $id;
            $model->name = $config['name'];
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
        if (array_key_exists($id, $this->fileConfig)) {
            $model = new Auth();
            $model->config = $this->fileConfig[$id];
            $model->name = $this->fileConfig[$id]['name'];
            $model->id = $id;
            return $model;
        } else {
            return null;
        }
    }

}
