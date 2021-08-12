<?php

namespace app\models;

use Yii;
use app\models\Auth;
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

    /**
     * @var string[] list of role names that cannot be altered or deleted
     **/
    public $immutableRoles = ['admin', 'teacher'];

    /**
     * @var yii\rbac\Item[]
     **/
    private $_childrenRBACObjects;

    /**
     * @var string[]
     **/
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
            [['children'], 'exist', 'targetAttribute' => 'name', 'allowArray' => true, 'message' => \Yii::t('user', 'This permission or role does not exist.')],
            [['name'], 'string', 'max' => 64],
            [['name'], 'unique'],
            [['name'], 'match',
                'pattern' => '/^[0-9a-zA-Z]+$/', // only alphanumeric characters
                'message' => \Yii::t('user', 'Only alphanumeric characters (0-9, a-z, A-Z) are allowed.')
            ],
            [['name'], 'prohibitLockoutByEdit'],
            [['name'], 'immutable', 'when' => function ($model) {
               return !$model->isNewRecord;
            }],
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
            'children' => \Yii::t('user', 'Permissions'),
            'created_at' => \Yii::t('user', 'Created At'),
            'updated_at' => \Yii::t('user', 'Updated At'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'name' => \Yii::t('user', 'A unique name for the role.'),
            'description' => \Yii::t('user', 'A short description.'),
            'children' => \Yii::t('user', 'A role is a collection of permissions given by explicit permissions or other roles. Select permissions and/or roles to be associated to this role.'),
        ];
    }

    /**
     * Generates an error message
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
     * Generates an error message if an attribute is edited
     *
     * @param string $attribute - the attribute
     * @param array $params
     * @return void
     */
    public function immutable($attribute, $params)
    {
        if ($this->oldAttributes[$attribute] !== $this->{$attribute}) {
            $this->addError($attribute, \Yii::t('user', "The attribute '{attribute}' is not editable. Remove the role and create a new one if you want to change that attribute.", [
                'attribute' => $this->attributeLabels()[$attribute],
            ]));
            $this->{$attribute} = $this->oldAttributes[$attribute];
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
            Yii::$app->session->addFlash('danger', \Yii::t('user', "The role '{role}' has still users associated to. Only roles without users can be deleted.", ['role' => $this->name]));
            return false;
        }

        if (!empty($auth->getChildren($this->name))) {
            Yii::$app->session->addFlash('danger', \Yii::t('user', "The role '{role}' has still permissions associated to. Only roles without permissions can be deleted.", ['role' => $this->name]));
            return false;
        }


        foreach (Yii::$app->auth->methods as $key => $config) {
            $method = Auth::findOne($key);
            if (in_array($this->name, $method->mapping)) {
                Yii::$app->session->addFlash('danger', \Yii::t('user', "The role '{role}' appears in the group mapping of the authentication method {method}. Only roles without any associations can be deleted.", [
                    'role' => $this->name,
                    'method' => substitute('{0} ({1})', [$method->name, $method->typeName])
                ]));
                return false;
            }
        }

        return true;
    }

    /**
     * Getter for the children property
     * The return array looks as: ['ticket/index', 'exam/index', ...]
     *
     * @return string[]
     */
    public function getChildren()
    {
        if ($this->_children === null) {
            $this->_children = array_column($this->childrenRBACObjects, 'name');
        }
        return $this->_children;
    }

    /**
     * Setter for the children property
     * @param array $children the array should look like: ['ticket/index', 'exam/index', ...]
     */
    public function setChildren($children)
    {
        $children = empty($children) ? [] : $children;
        $this->_children = $children;
        $auth = Yii::$app->authManager;
        $this->_childrenRBACObjects = array_map(function($item) use ($auth) {
            $perm = $auth->getPermission($item);
            return $perm !== null ? $perm : $auth->getRole($item);
        }, $children);
    }

    /**
     * @return yii\rbac\Item[]
     */
    public function getChildrenRBACObjects()
    {
        if ($this->_childrenRBACObjects === null) {
            $auth = Yii::$app->authManager;
            $this->_childrenRBACObjects = $auth->getChildren($this->name);
        }
        return $this->_childrenRBACObjects;
    }

    /**
     * @return app\models\Role[]
     */
    public function getChildrenObjects()
    {
        return self::find()->where(['name' => $this->children])->all();
    }

    /**
     * Returns an multidimensional array of hierarchical children, for example:
     * [
     *     'ticket/index' => [
     *         'name' => 'ticket/index',
     *         'description' => 'Index all tickets',
     *         'children' => [
     *             'exam/index' => [
     *                 'name' => 'exam/index',
     *                 'description' => 'Index all exams',
     *                 'children' => [
     *                     ...
     *                 ],
     *             ],
     *         ],
     *     ],
     * ]
     * @return array
     */
    public function getChildrenHierarchical()
    {
        return ArrayHelper::map(self::find()->where(['name' => $this->children])->all(), 'name', function($obj) {
            return ArrayHelper::toArray($obj, [
                'app\models\Role' => [
                    'name',
                    'description' => function($role) { return Yii::t('permission', $role->description); },
                    'children' => function($role) { return $role->childrenHierarchical; }
                ]
            ]);
        });
    }

    /**
     * List of children for the form.
     * The return array looks as:
     * [
     *      'ticket/index' => 'Index tickets of own exams (ticket/index)',
     *      'exam/index' => 'Index own exams (exam/index)',
     *      ...
     * ]
     * @return array
     */
    public function getChildrenFormList()
    {
        return ArrayHelper::map($this->childrenRBACObjects, 'name', function($model) {
            return substitute('{description} ({name})', [
                'name' => $model->name,
                'description' => $model->description,
            ]);
        });
    }

    /**
     * @inheritdoc
     */
    public function insert($runValidation = true, $attributes = null)
    {
        if ($runValidation && !$this->validate($attributes)) {
            Yii::info('Model not inserted due to validation error.', __METHOD__);
            return false;
        }

        $auth = Yii::$app->authManager;
        $role = $auth->createRole($this->name);
        $role->description = $this->description;
        if ($auth->add($role)) {
            return $this->updateChildren();
        } else {
            $this->addError('name', \Yii::t('user', "The role '{role}' could not be added succesfully.", ['role' => $this->name]));
            return false;
        }
    }

    /**
     * Updates the child objects of a role.
     * 
     * @return bool whether the child objects could be updated or not
     */
    private function updateChildren()
    {
        $auth = Yii::$app->authManager;
        $role = $auth->getRole($this->name);
        $auth->removeChildren($role);
        foreach ($this->_children as $permission) {
            if (($child = $auth->getPermission($permission)) === null){
                $child = $auth->getRole($permission);
            }
            if ($child !== null) {
                if ($auth->canAddChild($role, $child)) {
                    $auth->addChild($role, $child);
                } else {
                    $this->addError('children', \Yii::t('user', "Cannot add '{child}' as a child of {role}, because this produces a loop.", [
                        'child' => $child->name,
                        'role' => $this->name,
                    ]));
                    return false;
                }
            } else {
                $this->addError('children', \Yii::t('user', "No such permission or role: '{item}'.", [
                    'item' => $permission,
                ]));
                return false;
            }
        }
        $auth->invalidateCache(); // flush the RBAC cache, else permissions might not be up-to-date
        return 1;
    }

    /**
     * @inheritdoc
     */
    public function update($runValidation = true, $attributeNames = null)
    {
        if ($runValidation && !$this->validate($attributeNames)) {
            Yii::info('Model not updated due to validation error.', __METHOD__);
            return false;
        }

        $auth = Yii::$app->authManager;
        $old_role = self::findOne($this->name);

        if (($role = $auth->getRole($this->name)) !== null) {
            $role->description = $this->description;

            if ($auth->update($this->name, $role)) {
                return $this->updateChildren();
            } else {
                $this->addError('name', \Yii::t('user', "The role '{role}' could not be updated succesfully.", ['role' => $this->name]));
                return false;
            }

        } else {
            $this->addError('name', \Yii::t('user', "The role '{role}' does not exist (anymore).", ['role' => $this->name]));
            return false;
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
