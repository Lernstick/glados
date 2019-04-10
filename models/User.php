<?php

namespace app\models;

use Yii;
use app\models\Base;
use yii\web\IdentityInterface;
use app\models\EventItem;

/**
 * This is the model class for table "user".
 *
 * @property integer $id
 * @property string $access_token
 * @property string $auth_key
 * @property string $username
 * @property string $password
 * @property string $role
 * @property string $last_visited
 * @property string $activities_last_visited
 * @property string $change_password
 *
 * @property AuthAssignment[] $authAssignments
 * @property AuthItem[] $itemNames
 * @property Exam[] $exams
 * @property Role $role
 */

class User extends Base implements IdentityInterface
{

    const SCENARIO_CREATE = 'create';
    const SCENARIO_UPDATE = 'update';
    const SCENARIO_PASSWORD_RESET = 'password_reset';
    public $password_repeat;
    private $_role;

    /**
     * @var array An array holding the values of the record before changing
     */
    private $presaveAttributes;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $instance = $this;
        $this->on(self::EVENT_BEFORE_UPDATE, function($instance){
            $this->presaveAttributes = $this->getOldAttributes();
        });
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        return [
            self::SCENARIO_CREATE => ['username', 'password', 'password_repeat', 'role', 'change_password'],
            self::SCENARIO_UPDATE => ['username', 'role', 'change_password'],
            self::SCENARIO_PASSWORD_RESET => ['password', 'password_repeat', 'change_password'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'password', 'password_repeat', 'role', 'activities_last_visited'], 'safe'],
            [['username', 'password', 'password_repeat', 'role'], 'required', 'on' => self::SCENARIO_CREATE],
            [['username', 'role'], 'required', 'on' => self::SCENARIO_UPDATE],
            [['password', 'password_repeat'], 'required', 'on' => self::SCENARIO_PASSWORD_RESET],
            [['username'], 'unique'], 
            [['username', 'password'], 'string', 'max' => 40],
            ['password_repeat', 'compare', 'compareAttribute' => 'password', 'message' => "Passwords don't match" ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => \Yii::t('users', 'ID'),
            'username' => \Yii::t('users', 'Username'),
            'password' => \Yii::t('users', 'Password'),
            'password_repeat' => \Yii::t('users', 'Repeat Password'),
            'last_visited' => \Yii::t('users', 'Last Visited'), 
            'role' => \Yii::t('users', 'Role'),
            'change_password' => \Yii::t('users', 'User has to change password at next login'),
        ];
    }

   /**
    * @return \yii\db\ActiveQuery
    */
    public function getAuthAssignments()
    {
        return $this->hasMany(AuthAssignment::className(), ['user_id' => 'id']);
    }

   /**
    * @return \yii\db\ActiveQuery
    */
    public function getItemNames()
    {
        return $this->hasMany(AuthItem::className(), ['name' => 'item_name'])->viaTable('auth_assignment', ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExams()
    {
        return $this->hasMany(Exam::className(), ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEvents() {
        return $this->hasMany(EventItem::className(), ['id' => 'event_id'])
            ->viaTable('rel_event_user', ['user_id' => 'id']);
    }

    /**
     * @return \yii\rbac\Role
     */
    public function getRole()
    {
        if(!isset($this->_role)){
            $auth = Yii::$app->authManager;
            $roles = $auth->getRolesByUser($this->id);
            $this->_role = empty($roles) ? '' : array_values($roles)[0]->name;
        }
        return $this->_role;
    }

    /**
     * @param  string      $role role to set
     * @return void
     */
    public function setRole($role)
    {
        $this->_role = $role;
/*        $auth = Yii::$app->authManager;
        $role = $auth->getRole($value);
        $auth->revokeAll($this->getOldAttribute('username'));
        $auth->assign($role, $this->username);*/
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['access_token' => $token]);
    }

    /**
     * Finds user by username
     *
     * @param  string      $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username]);
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param  string  $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->getSecurity()->validatePassword($password, $this->password);
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $this->auth_key = \Yii::$app->security->generateRandomString();
                $this->password = Yii::$app->getSecurity()->generatePasswordHash($this->password);
            }
            if ($this->scenario == self::SCENARIO_PASSWORD_RESET) {
                $this->password = Yii::$app->getSecurity()->generatePasswordHash($this->password);
            }
            if ($this->scenario == self::SCENARIO_PASSWORD_RESET && $this->id == Yii::$app->user->identity->id) {
                $this->change_password = 0;
            }
            return true;
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        $auth = Yii::$app->authManager;
        $role = $auth->getRole($this->role);
        $auth->revokeAll($this->id);
        $auth->assign($role, $this->id);
    }

}
