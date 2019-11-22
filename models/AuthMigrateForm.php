<?php

namespace app\models;

use Yii;
use yii\base\Model;
use app\models\Auth;
use app\models\User;

/**
 * AuthMigrateForm is the model behind the migration form.
 */
class AuthMigrateForm extends Model
{
    /**
     * @const string Scenario to migrate
     */
    const SCENARIO_MIGRATE = 'migrate';

    /**
     * @var string Authentication method id to migrate users from.
     */
    public $from = 0;

    /**
     * @var string Authentication method id to migrate users to.
     */
    public $to;

    /**
     * @var array Array of user ids that are able to migrate
     */
    public $users = [];

    /**
     * @var array Array of errors of User models after save()-ing
     */
    public $userErrors = [];

    /**
     * @var Auth Authentication object of [[to]].
     */
    private $_toModel;

    /**
     * @var Auth Authentication object of [[from]].
     */
    private $_fromModel;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['from', 'to'], 'required'],
            ['to', 'compare',
                'compareAttribute' => 'from',
                'message' => Yii::t('auth', 'You cannot migrate users from an authentication method to itself.'),
                'operator' => '!=',
            ],
            ['users', 'filter',
                'filter' => [$this, 'processUsers'],
                'on' => self::SCENARIO_MIGRATE
            ],
            ['users', 'required', 'on' => self::SCENARIO_MIGRATE],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'login' =>\Yii::t('auth', 'Query Credentials'),
            'from' => \Yii::t('auth', 'From'),
            'to' => \Yii::t('auth', 'To'),
            'users' => \Yii::t('auth', 'Users to migrate'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'login' => \Yii::t('auth', 'You can provide query credentials here, to query the servers of <code>{to}</code> for all users that are currently authenticatied via <code>{from}</code>. <i>Login credentials are not saved anywhere.</i>', [
                'from' => array_key_exists($this->from, AuthSearch::getAuthSelectList()) ? AuthSearch::getAuthSelectList()[$this->from] : $this->from,
                'to' => array_key_exists($this->to, AuthSearch::getAuthSelectList()) ? AuthSearch::getAuthSelectList()[$this->to] : $this->to,
            ]),
            'from' => \Yii::t('auth', 'Authentication method to migrate users from. After the migration, all migrated users will <b>not anymore</b> authenticate over this method.'),
            'to' => \Yii::t('auth', 'Authentication method to migrate users to. After the migration, all migrated users will authenticate over this method.'),
            'users' => \Yii::t('auth', 'Choose users to migrate. Selected users will be migrated from <code>{from}</code> to <code>{to}</code>.', [
                'from' => array_key_exists($this->from, AuthSearch::getAuthSelectList()) ? AuthSearch::getAuthSelectList()[$this->from] : $this->from,
                'to' => array_key_exists($this->to, AuthSearch::getAuthSelectList()) ? AuthSearch::getAuthSelectList()[$this->to] : $this->to,
            ]),
        ];
    }

    /**
     * Compute the users array
     * @param array $arr the array from the POST request
     * @return array the new users array in the format
     * 
     * [
     *      'id'  => 'identifier',
     *      '10'  => '70e0bef2-b2a6-4b40-bf01-7c69f7a89eee',
     *      '132' => '14',
     *      '5'   => '1000',
     * ]
     *
     * 'id' is the id from the database and 'identifier' is the identifier found in the LDAP
     * 
     */
    public function processUsers ($arr) {
        // compute the new users array here

        if ($arr === '') {
            $arr = [];
        }

        $users = [];
        foreach ($arr as $key => $string) {
            // split the string "id -> identifier"
            list($id, $identifier) = array_values(preg_split('/\ \-\>\ /', $string, 2));
            $users[$id] = $identifier;
        }
        return $users;
    }

    /**
     * @return Auth Authentication object of [[to]].
     */
    public function getToModel()
    {
        if ($this->_toModel == null) {
            $this->_toModel = Auth::findOne($this->to);
        }
        return $this->_toModel;
    }

    /**
     * @return Auth Authentication object of [[from]].
     */
    public function getFromModel()
    {
        if ($this->_fromModel == null) {
            $this->_fromModel = Auth::findOne($this->from);
        }
        return $this->_fromModel;
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        if ($this->validate()) {

            $users = User::find()->where([
                'type' => $this->from,
                'id' => array_keys($this->users)
            ])->all();

            foreach($users as $user) {
                $identifier = $this->users[$user->id] === "NULL" ? null : $this->users[$user->id];
                $user->scenario = User::SCENARIO_UPDATE;
                $user->type = $this->to;
                $user->identifier = $identifier;
                if ($user->save() !== true) {
                    $err = $user->firstErrors;
                    $this->userErrors[$user->id] = reset($err);
                }
            }
            return true;
        }
        return false;
    }

}
