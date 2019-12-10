<?php

namespace app\components;
 
use Yii;
use yii\base\Component;
use app\models\User;
use yii\base\InvalidConfigException;

/**
 * AuthOpenLdap represents a connection to an Openldap server.
 *
 * @inheritdoc
 */
class AuthOpenLdap extends AuthGenericLdap
{

    /**
     * @inheritdoc
     */
    public $class = 'app\components\AuthOpenLdap';

    /**
     * @inheritdoc
     */
    public $type = \app\models\Auth::AUTH_OPENLDAP;

    /**
     * @inheritdoc
     */
    public $typeName = 'OpenLDAP';

    /**
     * @inheritdoc
     */
    public $name = 'LDAP';

    /**
     * @inheritdoc
     */
    public $description = 'OpenLDAP Authentication Method';


    /**
     * @inheritdoc
     */
    public $uniqueIdentifier = 'uid';

    /**
     * @inheritdoc
     */
    public $groupIdentifier = 'cn';
    public $userIdentifier = 'uid';

    /**
     * @inheritdoc
     */
    public $loginSearchFilter = '(& {userSearchFilter} (uid={username}) )';

    /**
     * @inheritdoc
     */
    public $groupSearchFilter = '(objectClass=posixGroup)';

    /**
     * @inheritdoc
     */
    public $userSearchFilter = '(objectClass=posixAccount)';

    /**
     * @inheritdoc
     */
    public $loginAttribute = 'uid';

    /**
     * @inheritdoc
     */
    public $bindAttribute = 'dn';

    /**
     * @inheritdoc
     */
    public $groupMemberAttribute = 'memberUid';

    /**
     * @inheritdoc
     */
    public $groupMemberUserAttribute = 'uid';

    /**
     * @inheritdoc
     */
    public $primaryGroupUserAttribute = 'gidNumber';

    /**
     * @inheritdoc
     */
    public $primaryGroupGroupAttribute = 'gidNumber';

}