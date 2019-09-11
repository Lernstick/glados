<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * This is the model class for the Auth class.
 *
 * @property app\components\Ad|null $obj The instantiated configuration object of the authentication type. This property is read-only.
 * @property array $configArray
 */
class Auth extends Model
{

    /* authentication type constants */
    const LDAP = 0;
    const ACTIVE_DIRECTORY = 1;
    
    public $methods = [];

    //public $configPath = __DIR__ . '/../config';
    const PATH = __DIR__ . '/../config';

    public $dirtyAttributes; //TODO
    public $isNewRecord; //TODO

    public $id;

    public $domain;
    public $mapping;
    public $ldap_uri;
    public $loginScheme;
    public $bindScheme;
    public $searchFilter;

    /**
     * @var string The class of the current authentication type.
     */
    public $class = 'app\models\Auth';

    /**
     * @var string The type of the current authentication type.
     */
    public $type;

    /**
     * @var string The view for the current authentication type.
     */
    public $view = 'view';

    /**
     * @var string The edit form view for the current authentication type.
     */
    public $form = '_form';

    /**
     * @var string A name for the current authentication type.
     */
    public $name;

    /**
     * @var string A description for the current authentication type.
     */
    public $description;

    private $_obj = null;
    private $_configArray = null;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['class'], 'safe'],
            [['name', 'class'], 'required'],
        ];
    }

    /**
     * @return array customized attribute labels
     */
    public function attributeLabels()
    {
        return [
            'class' => Yii::t('auth', 'Method'),
            'name' => Yii::t('auth', 'Name'),
            'description' => Yii::t('auth', 'Description'),
            'config' => Yii::t('auth', 'Konfiguration'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'name' => \Yii::t('auth', 'The name of this authentication method. Could be the (short) name of the school, or just <code>LDAP</code> or <code>AD</code>'),
            'class' => \Yii::t('auth', 'Choose one the available authentcation methods from the list below.'),
        ];
    }

    /**
     * Mapping of the different types and names
     *
     * @return array Array whose keys are the types and values are names.
     */
    public function getTypeMap()
    {
        return [
            0 => 'LDAP',
            1 => 'Active Directory',
        ];
    }

    /**
     * Getter
     */
    public function getConfigArray()
    {
        if(!isset($this->_configArray)){
            $this->_configArray = [
                'class' => $this->class,
                'name' => $this->name,
                'description' => $this->description,
                'form' => $this->form,
                'view' => $this->view,
                'type' => $this->type,
            ];
        }
        return $this->_configArray;
    }

    /**
     * Setter
     */
    public function setConfigArray($array)
    {
        $this->_configArray = $array;
    }

    /**
     * Getter
     */
    public function getAuthList()
    {
        return [
            'app\components\Ad' => \Yii::t('auth', 'Active Directory'),
            'app\components\Ldap' => \Yii::t('auth', 'LDAP'),
        ];
    }


    /**
     *
     */
    public function getFileConfig()
    {
        $config = require(self::PATH . '/auth.php');
        return array_key_exists('methods', $config)
            ? $config['methods']
            : null;
    }

    /**
     * Getter for the instantiated object of app\components\Auth_type
     * Possible objects are:
     *  * app\models\Auth
     *  * app\components\Ad
     * 
     * @return app\models\Auth|app\components\Ad|null instantiated object
     */
    public function getObj()
    {

        if(!isset($this->_obj)){
            $this->_obj = Yii::createObject($this->configArray);
        }
        return $this->_obj;
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
            //$model->id = $id;
            //$model->name = $configArray['name'];
            $model->obj->id = $id;
            $models[] = $model->obj;
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
            //$model->name = $configArray[$id]['name'];
            //$model->id = $id;
            $model->obj->id = $id;
            return $model->obj;
        } else {
            return null;
        }
    }

}
