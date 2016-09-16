<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
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
 *
 * @property AuthAssignment[] $authAssignments
 * @property AuthItem[] $itemNames
 * @property Exam[] $exams
 */

class User extends ActiveRecord implements IdentityInterface
{

    const SCENARIO_CREATE = 'create';
    const SCENARIO_UPDATE = 'update';
    const SCENARIO_PASSWORD_RESET = 'password_reset';
    public $password_repeat;
    private $_role;

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
            self::SCENARIO_CREATE => ['username', 'password', 'password_repeat', 'role'],
            self::SCENARIO_UPDATE => ['username', 'role'],
            self::SCENARIO_PASSWORD_RESET => ['password', 'password_repeat'],
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
            'id' => 'ID',
            'username' => 'Username',
            'password' => 'Password',
            'password_repeat' => 'Repeat Password',
            'last_visited' => 'Last Visited', 
            'role' => 'Role',
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

    public function getRole()
    {
        if(!isset($this->_role)){
            $auth = Yii::$app->authManager;
            $roles = $auth->getRolesByUser($this->id);
            $this->_role = empty($roles) ? '' : array_values($roles)[0]->name;
        }
        return $this->_role;
    }

    public function setRole($value)
    {
        $this->_role = $value;
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

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $this->auth_key = \Yii::$app->security->generateRandomString();
            }
            $this->password = Yii::$app->getSecurity()->generatePasswordHash($this->password);
            return true;
        }
        return false;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        $auth = Yii::$app->authManager;
        $role = $auth->getRole($this->role);
        $auth->revokeAll($this->id);
        $auth->assign($role, $this->id);
    }

}
