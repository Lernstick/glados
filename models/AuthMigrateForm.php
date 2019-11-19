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
     * @var array Array of usernames that are able to migrate
     */
    public $users = [];

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
            ['users', 'required', 'on' => self::SCENARIO_MIGRATE],
            ['users', 'filter', 'filter' => [$this, 'processUsers'], 'on' => self::SCENARIO_MIGRATE],
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
            'from' => \Yii::t('auth', 'TODO'),
            'to' => \Yii::t('auth', 'TODO'),
            'users' => \Yii::t('auth', 'Choose users to migrate. Selected users will be migrated from <code>{from}</code> to the authentication method <code>{to}</code>.', [
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
     *      'identifier'                            => 'username',
     *      '70e0bef2-b2a6-4b40-bf01-7c69f7a89eee'  => 'user_1',
     *      '14'                                    => 'user2',
     * ]
     * 
     */
    public function processUsers ($arr) {
        // compute the new users array here
        $users = [];
        foreach ($arr as $key => $string) {
            list($username, $identifier) = array_values(preg_split('/\ \-\>\ /', $string, 2));
            $users[$username] = $identifier;
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
                'username' => array_keys($this->users)
            ])->all();

            foreach($users as $user) {
                $identifier = $this->users[$user->username] === "NULL" ? null : $this->users[$user->username];
                $user->scenario = User::SCENARIO_UPDATE;
                $user->type = $this->to;
                $user->identifier = $identifier;
                $user->save();
            }
            return true;
        }
        return false;
    }

}
