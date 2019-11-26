<?php

namespace app\components;
 
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use app\models\User;
use app\models\AuthInterface;

/**
 * Local represents a local authentication via db
 *
 */
class Local extends \app\models\Auth implements AuthInterface
{

    /**
     * @inheritdoc
     */
    public $id = 'init';

    /**
     * @inheritdoc
     */
    public $class = 'app\components\Local';

    /**
     * @inheritdoc
     */
    public $type = \app\models\Auth::AUTH_LOCAL;

    /**
     * @inheritdoc
     */
    public $typeName = 'Database';

    /**
     * @inheritdoc
     */
    public $name = 'Local';

    /**
     * @inheritdoc
     */
    public $description = 'Local Database Authentication';

    /**
     * @inheritdoc
     */
    public $loginScheme = '{username}';

    /**
     * @inheritdoc
     */
    public $view = 'view_local';

    /**
     * @inheritdoc
     */
    public $order = 0;

    /**
     * @inheritdoc
     */
    public $migrationForm = '_form_local_migrate';

    /**
     * @inheritdoc
     *
     * @param string $attribute the attribute currently being validated
     * @param mixed $params the value of the "params" given in the rule
     * @param \yii\validators\InlineValidator $validator related InlineValidator instance.
     */
    public function queryUsers ($attribute, $params, $validator)
    {
        $localUsers = $this->findMigratableUsers(['and',
            ['not', ['password' => null]],
            ['not', ['password' => '']],
        ]);

        if (count($localUsers) !== 0) {
            $this->migrateUsers = [];
            foreach ($localUsers as $id => $username) {
                $this->addMigrateUser([
                    'id' => $id,
                    'identifier' => 'NULL',
                    'username' => $username,
                ]);
            }
        }
    }

    /**
     * Authenticate a user locally.
     * 
     * @param string $username the username given from the login form
     * @param string $password the password given from the login form
     * @return bool authentication success or failure
     */
    public function authenticate($username, $password)
    {
        $this->debug[] = Yii::t('auth', 'Username <code>{username}</code> matches loginScheme <code>{loginScheme}</code>.', [
            'username' => $username,
            'loginScheme' => $this->loginScheme,
        ]);
        
        $user = User::findByUsername($username);
        if ($user) {
            $this->debug[] = Yii::t('auth', 'User found in database.');
            if ($user->validatePassword($password)) {
                $this->debug[] = Yii::t('auth', 'User role set to <code>{role}</code>.', [
                    'role' => $user->role
                ]);
                $this->success = Yii::t('auth', 'Authentication was successful.');
                return true;
            } else {
                $this->error = Yii::t('auth', 'User password wrong.');
            }
        } else {
            $this->error = Yii::t('auth', 'Username <code>{username}</code> not found in database.', [
                'username' => $username,
            ]);
        }
        return false;
    }


    /**
     * @inheritdoc
     */
    public function getMigrateFromDescription ()
        {
            return yiit('auth', 'The following users are currently associated to the <code>Local</code> authentication method.');
        }

    /**
     * @inheritdoc
     */
    public function getMigrateToDescription ()
        {
            return yiit('auth', 'In the list below, only users that have a local password are listed, because only these users can login after a migration and are able to be migrated. This is when the user was created as a local user in the first place.');
        }

    /**
     * The explanation of the local authentication method.
     * @return string the explanation
     */
    public function getExplanation ()
    {
        return Yii::t('auth', 'The local authentication method <b>cannot</b> be changed or deleted. It will always be set up as first configuration item with order 0. All login attempts will first be authenticated through this local method. This cannot be changed. If the authentication fails via local database in any way (username does not exist / password is wrong), the authentication method list will be processed further in the given order.');
    }

}