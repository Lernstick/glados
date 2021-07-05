<?php

namespace app\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "auth_item".
 *
 * @property string $name
 * @property string $description
 * @property integer $type
 * @property integer $created_at
 * @property integer $updated_at
 *
 */
class Role extends Base
{

    const TYPE = 1;

    public $immutableRoles = ['admin'];

    private $_children;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'auth_item';
    }


    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('UNIX_TIMESTAMP()'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['created_at', 'updated_at'], 'integer'],
            [['description'], 'string'],
            [['type'], 'default', 'value' => self::TYPE],
            [['children'], 'default', 'value' => []],
            [['children'], 'exist', 'targetAttribute' => 'name', 'allowArray' => true, 'message' => 'blabal'],
            [['name'], 'string', 'max' => 64],
            [['name'], 'unique'],
            [['name'], 'prohibitLockoutByEdit'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'name' => \Yii::t('user', 'Rolename'),
            'description' => \Yii::t('user', 'Description'),
            'created_at' => \Yii::t('user', 'Created At'),
            'updated_at' => \Yii::t('user', 'Updated At'),
        ];
    }

    /**
     * Generates an error message when the ticket is not in closed state
     *
     * @param string $attribute - the attribute
     * @param array $params
     * @return void
     */
    public function prohibitLockoutByEdit($attribute, $params)
    {
        if ($this->type != self::TYPE) {
            $this->addError($attribute, \Yii::t('user', "'{name}' is not a role. Only roles can be edited.", ['name' => $this->name]));
        }

        if (in_array($this->name, $this->immutableRoles)) {
            $this->addError($attribute, \Yii::t('user', "The '{role}' role is immutable and cannot be edited.", ['role' => $this->name]));
        }
    }

    /**
     * @inheritdoc
     * @see [[prohibitLockoutByEdit()]]
     */
    public function beforeDelete()
    {

        if (!parent::beforeDelete()) {
            return false;
        }

        if (in_array($this->name, $this->immutableRoles)) {
            Yii::$app->session->addFlash('danger', \Yii::t('user', "The '{role}' role is immutable and cannot be deleted.", ['role' => $this->name]));
            return false;
        }

        if ($this->type !== self::TYPE) {
            Yii::$app->session->addFlash('danger', \Yii::t('user', "'{name}' is not a role. Only roles can be deleted.", ['name' => $this->name]));
            return false;
        }

        $auth = Yii::$app->authManager;

        if (!empty($auth->getUserIdsByRole($this->name))) {        
            Yii::$app->session->addFlash('danger', \Yii::t('user', "'{name}' has still users associated to. Only roles without users can be deleted.", ['name' => $this->name]));
            return false;
        }

        if (!empty($auth->getChildren($this->name))) {
            Yii::$app->session->addFlash('danger', \Yii::t('user', "'{name}' has still permissions associated to. Only roles without permissions can be deleted.", ['name' => $this->name]));
            return false;
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getChildren()
    {
        if ($this->_children === null) {
            $auth = Yii::$app->authManager;
            $this->_children = ArrayHelper::map($auth->getChildren($this->name), 'name', function($model) {
                return substitute('{description} ({name})', [
                    'name' => $model->name,
                    'description' => $model->description,
                ]);
            });
        }
        return $this->_children;
    }

    /**
     * @inheritdoc
     */
    public function setChildren($children)
    {
        $this->_children = $children;
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        $auth = Yii::$app->authManager;
        $role = $auth->getRole($this->name);
        $auth->removeChildren($role);
        foreach ($this->_children as $permission) {
            if (($child = $auth->getPermission($permission)) === null){
                $child = $auth->getRole($permission);
            }
            if ($child !== null) {
                try {
                    $auth->addChild($role, $child);
                }
                catch (\yii\base\InvalidArgumentException $e) {
                    Yii::$app->session->addFlash('danger', \Yii::t('user', "Cannot add '{role}' as a child of itself.", ['role' => $this->name]));
                }
            }
        }
    }

    /**
     * @inheritdoc
     *
     * @return RoleQuery the active query used by this AR class.
     */
    public static function find()
    {
        $query = new RoleQuery(get_called_class());
        return $query;
    }

}
