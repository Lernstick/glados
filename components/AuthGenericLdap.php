<?php

namespace app\components;
 
use Yii;
use yii\base\Component;
use app\models\User;
use yii\base\InvalidConfigException;
use app\models\AuthInterface;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * AuthGenericLdap represents a connection to an generic LDAP directory.
 *
 * @property bool $isActive Whether the LDAP connection is established. This property is read-only.
 */
class AuthGenericLdap extends \app\models\Auth implements AuthInterface
{

    /**
     * @const string Scenario to query for group names
     */
    const SCENARIO_QUERY_GROUPS = 'query_groups';

    /**
     * @const string Bind directly to the LDAP using the username from the login form
     */
    const SCENARIO_BIND_DIRECT = 'bind_direct';

    /**
     * @const string Bind via a given bind user to the LDAP, then retrieve the bind attribute of the login user,
     * then bind again using this attribute to authentication the login user,
     */
    const SCENARIO_BIND_BYUSER = 'bind_byuser';

    /**
     * @const string Perform an anonymous bind, then retrieve the bind attribute of the login user,
     * then bind again using this attribute to authentication the login user,
     */
    const SCENARIO_ANONYMOUS_BIND = 'anonymous_bind';

    /**
     * @const int extended error output
     */
    const LDAP_OPT_DIAGNOSTIC_MESSAGE = 0x0032;

    public $ldap_uri = '';
    public $ldap_scheme = 'ldap';
    public $ldap_port = 389;
    public $domain = '';

    public $ldap_options = [
        LDAP_OPT_PROTOCOL_VERSION => 3,
        LDAP_OPT_REFERRALS => 0,
        LDAP_OPT_NETWORK_TIMEOUT => 5,
    ];
    public $base = '';

    /**
     * @inheritdoc
     */
    public $class = 'app\components\AuthGenericLdap';

    /**
     * @inheritdoc
     */
    public $type = \app\models\Auth::AUTH_LDAP;

    /**
     * @inheritdoc
     */
    public $typeName = 'Generic LDAP';

    /**
     * @inheritdoc
     */
    public $view = 'view_ldap';

    /**
     * @inheritdoc
     */
    public $form = '_form_ldap';

    /**
     * @inheritdoc
     */
    public $migrationForm = '_form_ldap_migrate';

    /**
     * @inheritdoc
     */
    public $name = 'LDAP';

    /**
     * @inheritdoc
     */
    public $description = 'Generic LDAP Authentication Method';


    /**
     * @var string This is the identifier value that is written in the user entry in the database later.
     */
    public $identifier;

    /**
     * @var string The role determined in [[determineRole()]].
     */
    public $role;

    /**
     * @var array array containing roles in the order of their priority. If a user has group
     * membership such that he can be associated to multiple roles, this array determines the
     * role to be associated. The user recieves the highest possible role.
     */
    public $roleOrder = [
        'admin',
        'teacher'
    ];

    public $connection;
    public $bind;

    /**
     * @var array Key-value pairs for mapping of LDAP groups (defaultly by their names) to roles.
     * 
     * Example:
     *
     * ```php
     * $mapping = [
     *     'LDAP-Admin-Group'            => 'admin',
     *     'LDAP-Teacher-Group'          => 'teacher',
     *     'Another-LDAP-Teacher-Group'  => 'teacher',
     * ];
     * ```
     * 
     * In the example above, if a user is in multiple groups appearing in the mapping, the highest
     * role according to [[roleOrder]] is taken for this user. Multiple LDAP groups can be mapped to the
     * same role. LDAP groups can be given in an arbitrary identifier.
     * @see groupIdentifier
     * @see roleOrder
     */
    public $mapping = [];

    /**
     * @var string A unique identifier across the LDAP Directory (that never changes).
     */
    public $uniqueIdentifier = 'uid';

    /**
     * @var string A (unique) identifier across the LDAP Directory for the LDAP groups for [[mapping]]. This
     * should be unique, as that it is used to identify the group membership.
     * @see mapping
     */
    public $groupIdentifier = 'cn';
    public $userIdentifier = 'uid';

    /**
     * @var array Array of common LDAP group/user identifier attribute names for the select list of
     * [[groupIdentifier]] and [[userIdentifier]].
     */
    public $identifierAttributes = [
        'sAMAccountName', //AD
        'distinguishedName', //AD
        'userPrincipalName', //AD
        'dn', //LDAP
        'cn',
        'name',
        'mail',
        'objectGUID', //AD
        'uid', //LDAP
        'userid', //OpenLDAP
        'uidNumber', //LDAP
        'gidNumber', //LDAP
    ];

    /**
     * @inheritdoc
     * @var string The pattern to test the given login credentials against.
     *
     * A login over LDAP will only be performed if the given username matches the provided
     * pattern. This is used to manage multiple LDAPs. `{username}` is extracted from the username given in the
     * login form. Later in the authentication `{username}` is replaced by the extracted one from here.
     * 
     * Examples:
     * 
     * ```php
     * $loginScheme = '{username}';   // no special testing, all usernames provided are authenticated against the LDAP.
     * $loginScheme = '{username}@foo';   // only usernames ending with "@foo" are considered and authenticated against the LDAP.
     * $loginScheme = '{username}@{domain}';   // only usernames ending with "@{domain}"" are considered and authenticated against the LDAP. {domain} is replaced with the given $domain configuration variable.
     * $loginScheme = 'foo\{username}';   // only usernames starting with "foo\" are considered and authenticated against the LDAP.
     * ```
     *
     * The placeholders that are replaced by the values given are: `{domain}` with [[domain]], `{netbiosDomain}`  with [[netbiosDomain]], `{base}` with with [[base]].
     * 
     * @see bindScheme
     * @see loginSearchFilter
     */
    public $loginScheme = '{username}';

    /**
     * @var string The pattern to build the login credentials for the bind to the LDAP.
     * 
     * `{username}` is the string corresponding to `{username}` extracted from [[$loginScheme]].
     * 
     * Examples:
     * 
     * ```php
     * $bindScheme = '{username}';       // no special altering, the username is taken as it is.
     * $bindScheme = '{username}@foo';   // the username is appended with "@foo" for authentication
     * $bindScheme = '{username}@{domain}';   // the username is appended with "@{domain}", where {domain} is replaced with the value given in the configuration
     * $bindScheme = 'cn={username},ou=People,dc=test,dc=local';   // a distinguished name is built out of the provided username. Instead of "dc=test,dc=local", one could have also used {base}.
     * $bindScheme = 'foo\{username}';   // the username is prepended with "foo\" for authentication
     * ```
     * 
     * The placeholders that are replaced by the values given are: `{domain}` with [[domain]], `{netbiosDomain}`  with [[netbiosDomain]], `{base}` with with [[base]].
     * 
     * @see loginScheme
     * @see loginSearchFilter
     */
    public $bindScheme = '{username}@{domain}';

    /**
     * @var string The search filter to query the LDAP for information on the current login user.
     * 
     * Unfortuately, we cannot use LDAPs extended operation for this (`ldap_exop()` with `LDAP_EXOP_WHO_AM_I`), since it needs PHP `>=7.2.0`.
     * `{username}` is the string corresponding to `{username}` extracted from [[$loginScheme]].
     * 
     * Examples:
     *
     * ```php
     * $loginSearchFilter = '(sAMAccountName={username})';  // search for entries matching the sAMAccountName
     * $loginSearchFilter = '(userPrincipalName={username}@foo)'; // search for entries matching the userPrincipalName to be appended by "@foo"
     * $loginSearchFilter = '(userPrincipalName={username}@{domain})'; // search for entries matching the userPrincipalName to be appended by "@{domain}", where {domain} is replaced with the value given in the configuration
     * $loginSearchFilter = '(dn=cn={username},ou=People,dc=test,dc=local)';    // search for entries matching the distinguished name. Instead of "dc=test,dc=local", one could have also used {base}.
     * ```
     * 
     * The placeholders that are replaced by the values given are: `{domain}` with [[domain]], `{netbiosDomain}` with [[netbiosDomain]], `{base}` with [[base]], `{username}` with the string extracted from [[$loginScheme]].
     *
     * @see https://www.php.net/manual/en/function.ldap-exop.php 
     * @see loginScheme
     */
    public $loginSearchFilter = '(& {userSearchFilter} (uid={username}) )';

    /**
     * @var string The search filter to query the LDAP for all group objects
     */
    public $groupSearchFilter = '(objectClass=groupOfNames)';

    /**
     * @var string The search filter to query the LDAP for all user objects
     */
    public $userSearchFilter = '(objectClass=inetOrgPerson)';

    /**
     * @var array Array of common search filters to use for group probing for the select list of [[groupSearchFilter]].
     */
    public $groupSearchFilterList = [
        '(objectCategory=group)' => '(objectCategory=group)',
        '(objectClass=posixGroup)' => '(objectClass=posixGroup)', //LDAP
        '(objectClass=groupOfNames)' => '(objectClass=groupOfNames)',
    ];

    /**
     * @var array Array of common search filters to use for user probing for the select list of [[groupSearchFilter]].
     */
    public $userSearchFilterList = [
        '(objectCategory=person)' => '(objectCategory=person)',
        '(objectClass=posixAccount)' => '(objectClass=posixAccount)', //LDAP
        '(objectClass=inetOrgPerson)' => '(objectClass=inetOrgPerson)', //LDAP
    ];

    /**
     * @var array Array of LDAP groups for the select list for the role mapping.
     */
    public $groups = [];

    /**
     * @var string The search filter to query the LDAP for user objects
     * The placeholders that are replaced by the values given are: {domain}, {netbiosDomain}, {base}, {userIdentifier}.
     */
    public $migrateUserSearchFilter = '(& {userSearchFilter} ({userIdentifier}={username}) )';

    public $query_username;
    public $query_password;

    /**
     * @var int method of authentication
     */
    public $method = self::SCENARIO_BIND_DIRECT;

    /**
     * @var string username for the bind
     */
    public $bindUsername;

    /**
     * @var string password for the bind
     */
    public $bindPassword;

    /**
     * @var string attribute that is used as login username
     */
    public $loginAttribute = 'uid';

    /**
     * @var string attribute to bind as login user
     */
    public $bindAttribute = 'dn';

    /**
     * @var string search filter to search for the login user entry in the LDAP
     */
    public $bindSearchFilter = '(& {userSearchFilter} ({loginAttribute}={username}) )';

    /**
     * @var string search filter to search for the group membership of the login user in the LDAP
     */
    public $groupMembershipSearchFilter = '(& {groupSearchFilter} (| ({groupMemberAttribute}={groupMemberUserIdentifier}) ({primaryGroupGroupAttribute}={primaryGroup})) )';
    /**
     * @var string attribute in the group object that refers to the user object
     * @see [[groupMemberUserAttribute]]
     */
    public $groupMemberAttribute = 'member';

    /**
     * @var string attribute name within the user object, that [[groupMemberAttribute]] is referring to.
     * @see [[groupMemberAttribute]]
     */
    public $groupMemberUserAttribute = 'dn';

    /**
     * @var array Array of common names of attributes for [[groupMemberAttribute]].
     */
    public $groupMemberAttributeList = [
        'memberUid' => 'memberUid',
        'member' => 'member',
        'UniqueMember' => 'UniqueMember',
    ];

    /**
     * @var string attribute name in the user object referring to the primary group of the user.
     * @see [[primaryGroupGroupAttribute]]
     */
    public $primaryGroupUserAttribute = 'gidNumber';

    /**
     * @var string attribute name in the group object, that [[primaryGroupUserAttribute]] is referring to.
     * @see [[primaryGroupUserAttribute]]
     */
    public $primaryGroupGroupAttribute = 'gidNumber';

    /**
     * @var array array|false holding user attributes queried from the LDAP
     */
    public $userObject;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->domain !== '') {
            if ($this->ldap_uri === '') {
                $this->ldap_uri = $this->ldap_scheme . '://' . $this->domain . ':' . $this->ldap_port;
            }
            /*if ($this->base === '') {
                $this->base = "dc=" . implode(",dc=", explode(".", $this->domain));
            }*/
        }

        if ($this->groups === []) {
            $this->groups = array_combine(array_keys($this->mapping), array_keys($this->mapping));
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            // required stuff in all scenarios
            [['domain', 'userSearchFilter', 'uniqueIdentifier', 'groupSearchFilter', 'groupIdentifier', 'groupMemberAttribute', 'groupMemberUserAttribute', 'primaryGroupUserAttribute','primaryGroupGroupAttribute', 'method'], 'required'],
            [['bindScheme', 'loginSearchFilter'], 'required', 'on' => self::SCENARIO_BIND_DIRECT],
            [['loginAttribute', 'bindAttribute'], 'required', 'on' => [self::SCENARIO_BIND_BYUSER, self::SCENARIO_ANONYMOUS_BIND]],
            [['bindUsername', 'bindPassword'], 'required', 'on' => self::SCENARIO_BIND_BYUSER],

            [['domain'], 'required', 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_BIND_DIRECT, self::SCENARIO_BIND_BYUSER, self::SCENARIO_ANONYMOUS_BIND, self::SCENARIO_QUERY_GROUPS]],

            ['mapping', 'filter', 'filter' => [$this, 'processMapping'], 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_BIND_DIRECT, self::SCENARIO_BIND_BYUSER, self::SCENARIO_ANONYMOUS_BIND, self::SCENARIO_QUERY_GROUPS]],

            ['method', 'filter', 'filter' => [$this, 'processMethod'], 'on' => [self::SCENARIO_BIND_DIRECT, self::SCENARIO_BIND_BYUSER, self::SCENARIO_ANONYMOUS_BIND, self::SCENARIO_QUERY_GROUPS]],

            [['migrateSearchPattern', 'userIdentifier', 'migrateUserSearchFilter', 'query_username', 'query_password'], 'safe', 'on' => self::SCENARIO_QUERY_USERS],

            [
                ['query_username', 'query_password'],
                'required',
                'when' => function($model) {return $model->scenario == self::SCENARIO_QUERY_GROUPS;},
                'whenClient' => "function (attribute, value) {
                    return $('#ldap-scenario').val() == 'query_groups';
                }",
                'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_BIND_DIRECT, self::SCENARIO_BIND_BYUSER]
            ],
            [
                'domain',
                'getAllLdapGroups',
                'when' => function($model) {return !empty($model->domain);},
                'on' => self::SCENARIO_QUERY_GROUPS
            ],

            [['migrateUserSearchFilter'], 'required',
                'when' => function($model) {return $model->scenario == self::SCENARIO_QUERY_USERS;},
                'whenClient' => "function (attribute, value) {
                    return $('#ldap-scenario').val() == 'query_users';
                }",
                'on' => [self::SCENARIO_MIGRATE, self::SCENARIO_QUERY_USERS]
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_BIND_DIRECT] = array_merge($scenarios[self::SCENARIO_DEFAULT], ['domain', 'ldap_uri', 'loginScheme', 'groupIdentifier', 'groupSearchFilter', 'mapping', 'uniqueIdentifier', 'groupMemberAttribute', 'groupMemberUserAttribute', 'userSearchFilter', 'primaryGroupUserAttribute', 'primaryGroupGroupAttribute', 'method', 'bindScheme', 'loginSearchFilter', 'bindAttribute']);
        $scenarios[self::SCENARIO_BIND_BYUSER] = array_merge($scenarios[self::SCENARIO_DEFAULT], ['domain', 'ldap_uri', 'loginScheme', 'groupIdentifier', 'groupSearchFilter', 'mapping', 'uniqueIdentifier', 'groupMemberAttribute', 'groupMemberUserAttribute', 'userSearchFilter', 'primaryGroupUserAttribute', 'primaryGroupGroupAttribute', 'method', 'bindUsername', 'bindPassword', 'loginAttribute', 'bindAttribute']);
        $scenarios[self::SCENARIO_ANONYMOUS_BIND] = array_merge($scenarios[self::SCENARIO_DEFAULT], ['domain', 'ldap_uri', 'loginScheme', 'groupIdentifier', 'groupSearchFilter', 'mapping', 'uniqueIdentifier', 'groupMemberAttribute', 'groupMemberUserAttribute', 'userSearchFilter', 'primaryGroupUserAttribute', 'primaryGroupGroupAttribute', 'method', 'loginAttribute', 'bindAttribute']);
        $scenarios[self::SCENARIO_QUERY_GROUPS] = array_merge($scenarios[self::SCENARIO_DEFAULT], ['domain', 'ldap_uri', 'loginScheme', 'groupIdentifier', 'groupSearchFilter', 'mapping', 'uniqueIdentifier', 'groupMemberAttribute', 'groupMemberUserAttribute', 'userSearchFilter', 'method', 'primaryGroupUserAttribute', 'primaryGroupGroupAttribute', 'bindUsername', 'bindPassword', 'loginAttribute', 'bindAttribute', 'query_username', 'query_password', 'bindScheme', 'loginSearchFilter']);
        $scenarios[self::SCENARIO_QUERY_USERS] = ['migrateSearchPattern', 'migrateUserSearchFilter', 'userIdentifier', 'query_username', 'query_password'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'domain' => Yii::t('auth', 'Domain'),
            'baseDn' => Yii::t('auth', 'Base DN'),
            'ldap_uri' => Yii::t('auth', 'LDAP URI'),
            'ldap_port' => Yii::t('auth', 'LDAP Port'),
            'ldap_scheme' => Yii::t('auth', 'LDAP Scheme'),
            'ldap_options' => Yii::t('auth', 'LDAP Options'),
            'loginScheme' => \Yii::t('auth', 'Login Scheme'),
            'bindScheme' => \Yii::t('auth', 'Bind Scheme'),
            'loginSearchFilter' => \Yii::t('auth', 'Login Search Filter'),
            'uniqueIdentifier' => \Yii::t('auth', 'Unique User Identifier Attribute'),
            'groupIdentifier' => \Yii::t('auth', 'Group Identifier Attribute'),
            'groupSearchFilter' => \Yii::t('auth', 'Group Search Filter'),
            'mapping' => \Yii::t('auth', 'Group Mapping'),
            'query_login' => \Yii::t('auth', 'Query Credentials'),
            'query_username' => \Yii::t('auth', 'Username'),
            'query_password' => \Yii::t('auth', 'Password'),
            'bindAttribute' => \Yii::t('auth', 'Bind Attribute'),
            'loginAttribute' => \Yii::t('auth', 'Login Attribute'),
            'userSearchFilter' => \Yii::t('auth', 'User Search Filter'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return array_merge(parent::attributeHints(), [
            'domain' => \Yii::t('auth', 'The full name of the LDAP Domain. For Exampe <code>test.local</code>.'),
            'ldap_uri' => Yii::t('auth', 'A full LDAP URI of the form <code>ldap://hostname:port</code> or <code>ldaps://hostname:port</code> for SSL encryption. You can also provide multiple LDAP-URIs separated by a space as one string. Note that <code>hostname:port</code> is not a supported LDAP URI as the schema is missing. See <a target="_blank" href="https://www.php.net/manual/en/function.ldap-connect.php">ldap_connect()</a>
                under <code>ldap_uri</code>.'),
            'ldap_options' => Yii::t('auth', 'For all possible options please visit <a target="_blank" href="https://www.php.net/manual/en/function.ldap-set-option.php">ldap_set_option()</a>'),
            'loginScheme' => \Yii::t('auth', 'The pattern to test the given login username against. A login via this authentication method will only be performed if the given username matches the provided pattern. This is used to manage multiple authentication servers and methods, for example multiple LDAP servers with different domains. <code>{username}</code> is extracted from the username provided in the login form according to the login Scheme.<br>Examples:<ul><li><code>{username}</code>: no special testing, all usernames provided are considered for authentication.</li><li><code>{username}@foo</code>: only usernames ending with <code>@foo</code> are considered.</li><li><code>foo\{username}</code>: only usernames starting with <code>foo\</code> are considered.</li><li><code>{username}@{domain}</code>: only usernames ending with <code>@{domain}</code> are considered, where <code>{domain}</code> is a placeholder that is replaced with the given value of <code>Domain</code>.</li></ul>For a full list of placeholders, please refer to {link1}.<br>For more information on this setting, refer to {link2}.', [
                    'link1' => Html::a('Placeholders', Url::to(['howto/view', 'id' => 'auth-placeholders.md'])),
                    'link2' => Html::a('Login Scheme', Url::to(['howto/view', 'id' => 'login-scheme.md'])),
                ]),
            'bindScheme' => \Yii::t('auth', 'The pattern to build the bind DN for the bind to the LDAP server. <code>{username}</code> is replaced with the string extracted from <code>{loginScheme}</code>.<br>Examples:<ul><li><code>{username}</code>: no special altering, the username is taken as bind DN as it is.</li><li><code>{username}@foo</code>: the username is appended with <code>@foo</code> to contruct the bind DN.</li><li><code>{username}@{domain}</code>: the username is appended with <code>@{domain}</code>, where <code>{domain}</code> is replaced with the value given in the configuration.</li><li><code>cn={username},dc=test,dc=local</code>: a distinguished name is built out of the provided username. Instead of <code>dc=test,dc=local</code>, one could have also used <code>{base}</code>.</li><li><code>foo\{username}</code>: the username is prepended with <code>foo\</code> for authentication.</li></ul>For a full list of placeholders, please refer to {link}.', [
                'loginScheme' => $this->getAttributeLabel('loginScheme'),
                'link' => Html::a('Placeholders', Url::to(['howto/view', 'id' => 'auth-placeholders.md'])),
            ]),
            'loginSearchFilter' => \Yii::t('auth', 'The search filter to query the LDAP for the object of the current user. <code>{username}</code> is replaced with the string extracted from <code>{loginScheme}</code>.<br>Examples:<ul><li><code>(sAMAccountName={username})</code>: search for entries matching the <code>sAMAccountName</code>.</li><li><code>(userPrincipalName={username}@foo)</code>: search for entries matching the <code>userPrincipalName</code> to be appended by <code>@foo</code>.</li><li><code>(userPrincipalName={username}@{domain})</code>: search for entries matching the <code>userPrincipalName</code> to be appended by <code>@{domain}</code>, where <code>{domain}</code> is replaced with the value given in the configuration.</li><li><code>(dn=cn={username},dc=test,dc=local)</code>: search for entries matching the distinguished name. Instead of <code>dc=test,dc=local</code>, one could have also used <code>{base}</code>.</li></ul>For a full list of placeholders, please refer to {link}.', [
                'link' => Html::a('Placeholders', Url::to(['howto/view', 'id' => 'auth-placeholders.md'])),
            ]),
            'groupIdentifier' => \Yii::t('auth', 'A (unique) human readable identifier across the LDAP Directory for groups. This should be unique, as that it is used to identify the group. This will be used for the group mapping.'),
            'groupSearchFilter' => \Yii::t('auth', 'The search filter for group objects.'),
            'mapping' => \Yii::t('auth', 'The direct assignment of LDAP groups to user roles. You can map multiple LDAP groups to the same role. Goups need not to be assigned to all roles.'),
            'query_login' => \Yii::t('auth', 'Username and password to query for group names, or leave empty for an anonymous bind. This information is only needed to specify the group mapping. <i>These login credentials will not be saved anywhere</i>.'),
            'bindAttribute' => \Yii::t('auth', 'The attribute that should be used as username to authenticate via LDAP.'),
            'loginAttribute' => \Yii::t('auth', 'The attribute that is used as username in the login form.'),
            'userSearchFilter' => \Yii::t('auth', 'The search filter for user objects.'),
            'uniqueIdentifier' => \Yii::t('auth', 'The attribute that serves as a unique identifier across the whole LDAP Directory. The values of this attribute should <b><i>never</i></b> change, even if the username is changed or the user object is moved in the directory tree.'),
            'groupMemberAttribute' => \Yii::t('auth', 'The attribute of the group object referring to the user object.'),
            'groupMemberUserAttribute' => \Yii::t('auth', 'The attribute of the user object, that <code>{other_attribute}</code> is referring to.', [
                'other_attribute' => $this->getAttributeLabel('groupMemberAttribute'),
            ]),
            'primaryGroupUserAttribute' => \Yii::t('auth', 'The attribute of the user object referring to the primary group of the user.'),
            'primaryGroupGroupAttribute' => \Yii::t('auth', 'The attribute of the group object, that <code>{other_attribute}</code> is referring to.', [
                'other_attribute' => $this->getAttributeLabel('primaryGroupUserAttribute'),
            ]),
            'method' => \Yii::t('auth', 'There are two differnt methods the LDAP server can be used to authenticate users.<ul><li><b>Bind directly by login credentials</b> means that the username from the login from (or parts of it) is used to bind to the LDAP server. <i>The authenticating user needs read permission on the LDAP server for this</i>.</li><li><b>Bind with given username and password</b> means that you have to provide credentials that are used to find the user object in the LDAP, before binding with the user itself. <i>The provided credentials need read permission on the LDAP server</i>, but the user itself does not.</li><li><b>Bind with anonymous user</b> means that the system performs an anonymous bind to find the user object in the LDAP, before binding with the user itself. <i>Anonymous binds must be allowed by the LDAP server for this</i>.</li></ul>Please read the descriptions of the settings below for details on the specific method.'),
            'migrateUserSearchFilter' => \Yii::t('auth', 'A search filter to query the LDAP for each username matching the condition to migrate. Placeholders can be used. For a full list of placeholders, please refer to {link}.', [
                'link' => Html::a('Placeholders', Url::to(['howto/view', 'id' => 'auth-placeholders.md'])),
            ]),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function substitutionProperties()
    {
        return [
            'domain' => $this->domain,
            'base' => $this->baseDn,
            'userSearchFilter' => $this->userSearchFilter,
            'groupSearchFilter' => $this->groupSearchFilter,
            'uniqueIdentifier' => $this->uniqueIdentifier,
            'userIdentifier' => $this->userIdentifier,
            'groupIdentifier' => $this->groupIdentifier,
            'groupMemberAttribute' => $this->groupMemberAttribute,
            'groupMemberUserAttribute' => $this->groupMemberUserAttribute,
            'primaryGroupUserAttribute' => $this->primaryGroupUserAttribute,
            'primaryGroupGroupAttribute' => $this->primaryGroupGroupAttribute,
            'loginAttribute' => $this->loginAttribute,
            'bindAttribute' => $this->bindAttribute,
        ];
    }

    /**
     * Defines attribute names and convertion functions.
     * The function should be a php callable with the following structure:
     * 
     *     @param string $value the value of the attribute
     *     @return string the converted string
     *     function ($value)
     * 
     * For example:
     * [
     *    //will call $this->convert() for each value of attribute with name "attr"
     *    'attr' => 'convert',
     *    //will call the given function for each value of attribute with name "attr"
     *    'attr' => function($v){ return strtolower($v); },
     * ]
     *
     * @return array Array of attribute names and functions.
     */
    public function postProcessRules()
    {
        return [];
    }

    /**
     * Applies post process rules to attribute values according to [[postProcessRules()]].
     * @param string $attr the attribute name
     * @param mixed $value the value of the attribute
     * @return string the converted string
     */
    public function postProcess($attr, $value)
    {
        // convert attribute data according to postProcessRules()
        if (array_key_exists($attr, $this->postProcessRules())) {
            $rule = $this->postProcessRules()[$attr];
            if (is_string($rule)) {
                $value = $this->{$rule}($value);
            } else if (is_callable($rule)) {
                $value = call_user_func($rule, $value);
            }
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function getMigrateFromDescription ()
        {
            return yiit('auth', 'The following users are currently associated to the {from} LDAP authentication method.');
        }

    /**
     * @inheritdoc
     */
    public function getMigrateToDescription ()
        {
            return yiit('auth', 'The users below where also found in the {to} LDAP Directory, and are therefore able to be migrated.');
        }

    /**
     * Returns a value indicating whether the LDAP connection is established.
     * @return bool whether the LDAP connection is established
     */
    public function getIsActive()
    {
        return $this->connection !== null;
    }

    /**
     * Getter for the baseDN.
     * @return string
     */
    public function getBaseDn()
    {
        if ($this->base !== '') {
            return $this->base;
        } else if ($this->domain !== '') {
            return "dc=" . implode(",dc=", explode(".", $this->domain));
        } else {
            return '';
        }
    }


    /**
     * Mapping of the different [[ldap_options]] to their names
     *
     * @return array Array whose keys are the ldap_options and values are names.
     */
    public function getLdap_options_name_map()
    {

        /**
         * All possible ldap constants according to php manual
         * @see https://www.php.net/manual/en/function.ldap-get-option.php
         */
        $ldap_constants = [
            'LDAP_OPT_DEBUG_LEVEL',
            'LDAP_OPT_DEREF',
            'LDAP_OPT_SIZELIMIT',
            'LDAP_OPT_TIMELIMIT',
            'LDAP_OPT_NETWORK_TIMEOUT',
            'LDAP_OPT_PROTOCOL_VERSION',
            'LDAP_OPT_ERROR_NUMBER',
            'LDAP_OPT_REFERRALS',
            'LDAP_OPT_RESTART',
            'LDAP_OPT_HOST_NAME',
            'LDAP_OPT_ERROR_STRING',
            'LDAP_OPT_DIAGNOSTIC_MESSAGE',
            'LDAP_OPT_MATCHED_DN',
            'LDAP_OPT_SERVER_CONTROLS',
            'LDAP_OPT_CLIENT_CONTROLS',
            'LDAP_OPT_X_KEEPALIVE_IDLE',
            'LDAP_OPT_X_KEEPALIVE_PROBES',
            'LDAP_OPT_X_KEEPALIVE_INTERVAL',
            'LDAP_OPT_X_TLS_CACERTDIR',
            'LDAP_OPT_X_TLS_CACERTFILE',
            'LDAP_OPT_X_TLS_CERTFILE',
            'LDAP_OPT_X_TLS_CIPHER_SUITE',
            'LDAP_OPT_X_TLS_CRLCHECK',
            'LDAP_OPT_X_TLS_CRLFILE',
            'LDAP_OPT_X_TLS_DHFILE',
            'LDAP_OPT_X_TLS_KEYFILE',
            'LDAP_OPT_X_TLS_PROTOCOL_MIN',
            'LDAP_OPT_X_TLS_RANDOM_FILE',
            'LDAP_OPT_X_TLS_REQUIRE_CERT',
        ];
        $retval = [];
        foreach ($ldap_constants as $key => $const) {
            if (defined($const) && array_key_exists(constant($const), $this->ldap_options)) {
                $retval[constant($const)] = $const;
            }
        }
        return $retval;
    }

    /**
     * Compute the mapping array
     * @param array arr the array from the POST request
     * @return array the new mapping array in the format of [[mapping]]
     */
    public function processMapping ($arr) {
        // compute the new mapping array here
        $mapping = [];
        foreach ($arr as $role => $array_of_groups) {
            foreach ($array_of_groups as $key => $group) {
                $mapping[$group] = $role;
            }
        }
        return $mapping;
    }

    /**
     * Process the method array
     * @param array arr the array from the POST request
     * @return array the new method attribute in the format of [[method]]
     */
    public function processMethod ($arr) {
        return $arr;
    }

    /**
     * Decides whether the username provided by the user matches the pattern to authenticate over LDAP.
     * @param string username the username that was provided to the login form by the user attempting to login
     *
     * @return false|string the extracted username or false
     */
    public function getRealUsername($username)
    {
        return $this->getRealUsernameByScheme($username, $this->loginScheme, $this->substitutionProperties());
    }

    /**
     * Contructs the username to bind to the LDAP.
     * @param string username the real username that was extracted from [[getRealUsername()]]
     * @return string the bind username
     */
    public function getBindUsername($username)
    {
        $username = $this->substitute($this->bindScheme, ['username' => $username]);
        Yii::debug('Username matches LDAP::loginScheme. Proceeding with bind username: ' . $username, __METHOD__);
        $this->debug[] = Yii::t('auth', 'Proceeding with bind username <code>{bindUser}</code> according to bindScheme <code>{bindScheme}</code>.', [
            'bindUser' => $username,
            'bindScheme' => $this->bindScheme,
        ]);
        return $username;
    }

    /**
     * Determines the highest possible roles for a set of given groups
     * 
     * @param array $groups set of LDAP groups
     * @return string|false the highest role according to [[roleOrder]] or the first element from the roles
     * array if nothing matches or false if roles is empty
     */
    public function determineRole($groups)
    {
        $roles = [];
        foreach ($this->mapping as $ldapGroup => $mappedRole) {
            if (in_array($ldapGroup, $groups)) {
                array_push($roles, $mappedRole);
            }
        }

        foreach ($this->roleOrder as $key => $role) {
            if (in_array($role, $roles)) {
                return $role;
            }
        }
        return array_key_exists(0, $roles) ? $roles[0] : false;
    }

    /**
     * Establishes an LDAP connection.
     * It does nothing if an LDAP connection has already been established.
     * @throws Exception if connection fails
     */
    public function open()
    {
        if ($this->connection !== null) {
            return;
        }

        if (empty($this->domain)) {
            $this->error = 'LDAP::domain cannot be empty.';
            throw new InvalidConfigException('LDAP::domain cannot be empty.');
        }

        if ($this->domain !== '' && $this->ldap_uri === '') {
            $this->ldap_uri = $this->ldap_scheme . '://' . $this->domain . ':' . $this->ldap_port;
        }

        Yii::debug('Opening LDAP connection: ' . $this->ldap_uri, __METHOD__);
        $this->debug[] = Yii::t('auth', 'Opening LDAP connection: <code>{uri}</code>.', [
            'uri' => $this->ldap_uri
        ]);
        $this->connection = ldap_connect($this->ldap_uri);

        if ($this->connection === false) {
            $this->error = 'LDAP::ldap_uri was not parseable.';
            throw new InvalidConfigException('LDAP::ldap_uri was not parseable.');
        }

        foreach ($this->ldap_options as $option => $value) {
            if(!ldap_set_option($this->connection, $option, $value)) {
                $this->error = 'Unable to set ' . $option . ' to ' . $value . '.';
                throw new NotSupportedException('Unable to set ' . $option . ' to ' . $value . '.');
            } else {
                Yii::debug('Setting ' . $this->ldap_options_name_map[$option] . ' to ' . $value . '.', __METHOD__);
                $this->debug[] = Yii::t('auth', 'Setting <code>{option}</code> to <code>{value}</code>.', [
                    'option' => $this->ldap_options_name_map[$option],
                    'value' => $value,
                ]);
            }
        }
    }

    /**
     * Bind to the LDAP.
     * 
     * @param string $username the username
     * @param string $password the password
     * @return false|string bind failure or the username
     */
    public function bindLdap($username, $password)
    {
        $this->open();
        $anonymous = false;
        if (empty($username) || empty($password)) {
            $anonymous = true;
        }

        $this->bind = @ldap_bind($this->connection, $username, $password);

        if ($this->bind) {
            Yii::debug('Bind successful', __METHOD__);
            if ($anonymous) {
                $this->debug[] = Yii::t('auth', 'Bind with anonymous user successful.');
            } else {
                $this->debug[] = Yii::t('auth', 'Bind with username <code>{username}</code> successful.', [
                    'username' => $username,
                ]);
            }
            return $username;
        } else {
            if ($anonymous) {
                $this->error = Yii::t('auth', 'Bind with anonymous user failed: {error}.', [
                    'error' => ldap_error($this->connection),
                ]);
            } else {
                $this->error = Yii::t('auth', 'Bind with username <code>{username}</code> failed: {error}.', [
                    'username' => $username,
                    'error' => ldap_error($this->connection),
                ]);
            }
            ldap_get_option($this->connection, AuthGenericLdap::LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error);
            if (!empty($extended_error)) {
                $this->error .= ", " . Yii::t('auth', "Detailed error message:") . " " . $extended_error;
            }
            Yii::debug($this->error, __METHOD__);
            $this->close();
            return false;
        }
    }

    /**
     * Authenticate a user over LDAP.
     * 
     * @param string $username the username given from the login form
     * @param string $password the password given from the login form
     * @return bool authentication success or failure
     */
    public function authenticate($username, $password)
    {
        $this->init();
        if (($user = $this->getRealUsername($username)) !== false) {
            $this->debug[] = Yii::t('auth', 'Username <code>{username}</code> matches loginScheme <code>{loginScheme}</code>.', [
                'username' => $username,
                'loginScheme' => $this->loginScheme,
            ]);
            if ($this->method == self::SCENARIO_BIND_BYUSER || $this->method == self::SCENARIO_ANONYMOUS_BIND) {
                if ($this->bindLdap($this->bindUsername, $this->bindPassword) !== false) {
                    if ( $this->getUserinfo($username) !== false ){
                        $groups = $this->getGroupMembership($username);
                        if ($this->bindLdap($this->userObject[$this->bindAttribute], $password) !== false) {
                            return $this->concludeAuthentication($username, $groups);
                        }
                    }
                }
            } else if ($this->method == self::SCENARIO_BIND_DIRECT) {
                if (($bindUser = $this->getBindUsername($user)) !== false) {
                    if ($this->bindLdap($bindUser, $password) !== false) {
                        if ( $this->getUserinfo($username) !== false ){
                            $groups = $this->getGroupMembership($username);
                            return $this->concludeAuthentication($username, $groups);
                        }
                    }
                }
            }
        } else {
            $this->error = Yii::t('auth', 'Username <code>{username}</code> does not match loginScheme <code>{loginScheme}</code> of authentication method <code>{name}</code>.', [
                'username' => $username,
                'loginScheme' => $this->loginScheme,
                'name' => $this->name,
            ]);
            Yii::debug($this->error, __METHOD__);
        }

        return false;
    }

    /**
     * Conclude the authentication.
     * 
     * @param string $username the username given from the login form
     * @param string $groups the groups array
     * @return bool authentication success or failure
     */
    public function concludeAuthentication($username, $groups)
    {
        $this->debug[] = Yii::t('auth', 'User group membership: <code>{groups}</code>.', [
            'groups' => implode('</code>, <code>', $groups),
        ]);
        if ( ($this->role = $this->determineRole($groups)) !== false) {
            $this->debug[] = Yii::t('auth', 'User role set to <code>{role}</code>.', [
                'role' => $this->role
            ]);
            $this->success = Yii::t('auth', 'Authentication was successful.');
            Yii::debug('role=' . $this->role . ', identifier=' . $this->identifier, __METHOD__);
            Yii::debug('Authentication was successful.', __METHOD__);
            return true;
        } else {
            $mapping = $this->mapping;
            array_walk($mapping, function(&$value, $key) {
                $value = "<code>{$key} -> {$value}</code>";
            });
            $this->debug[] = Yii::t('auth', 'Mapping: {mapping}.', [
                'mapping' => implode(", ", $mapping),
            ]);
            $this->error = 'No role found, check <code>roleOrder</code> and <code>mapping</code>.';
            Yii::debug($this->error, __METHOD__);
            $this->close();
            return false;
        }
    }

    /**
     * Query LDAP for user information and populate [[userObject]].
     * @param string $username
     * @return bool
     */
    public function getUserinfo($username)
    {
        if ($this->method == self::SCENARIO_BIND_BYUSER || $this->method == self::SCENARIO_ANONYMOUS_BIND) {
            $searchFilter = $this->substitute($this->bindSearchFilter, ['username' => $username]);
        } else {
            $searchFilter = $this->substitute($this->loginSearchFilter, ['username' => $username]);
        }

        $this->debug[] = Yii::t('auth', 'Querying LDAP for the user object with search filter <code>{searchFilter}</code> and base dn <code>{base}</code> for the attributes <code>{attributes}</code>.', [
            'searchFilter' => $searchFilter,
            'base' => $this->baseDn,
            'attributes' => implode("</code>, <code>", $this->userAttributes),
        ]);

        $this->userObject = $this->askFor($this->userAttributes, $searchFilter, [
            'limit' => 1,
            'checkAttribute' => 'bindSearchFilter',
        ]);
        $this->identifier = $this->userObject[$this->uniqueIdentifier];        

        if ($this->userObject !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return an array of user attribute to query
     * @return array 
     */
    public function getUserAttributes()
    {
        if ($this->method == self::SCENARIO_BIND_BYUSER || $this->method == self::SCENARIO_ANONYMOUS_BIND) {
            return [
                $this->bindAttribute,
                $this->uniqueIdentifier,
                $this->primaryGroupUserAttribute,
                $this->groupMemberUserAttribute
            ];
        } else if ($this->method == self::SCENARIO_BIND_DIRECT) {
            return [
                $this->uniqueIdentifier,
                $this->primaryGroupUserAttribute,
                $this->groupMemberUserAttribute
            ];
        }
    }

    /**
     * Query LDAP for group membership of the user, including the primary group
     *
     * @param string $username
     * @return array array of group identifiers / can be empty
     */
    public function getGroupMembership($username)
    {
        $groups = array();
        $searchFilter = $this->substitute($this->groupMembershipSearchFilter, [
            'primaryGroup' => $this->userObject[$this->primaryGroupUserAttribute],
            'groupMemberUserIdentifier' => $this->userObject[$this->groupMemberUserAttribute],
            'username' => $username,
        ]);
        $attributes = array($this->groupIdentifier);

        $this->debug[] = Yii::t('auth', 'Querying LDAP for group membership with search filter <code>{searchFilter}</code> and base dn <code>{base}</code> for the attributes <code>{attributes}</code>.', [
            'searchFilter' => $searchFilter,
            'base' => $this->baseDn,
            'attributes' => implode("</code>, <code>", $attributes),
        ]);

        if ( ($ret = $this->askFor($attributes, $searchFilter, ['limit' => 0, 'returnArray'])) !== false) {
            $groups = $ret[$this->groupIdentifier];
        }
        return $groups;
    }

    /**
     * Query LDAP for attributes with search filter
     *
     * @param array $attributes
     * @param string $searchFilter
     * @param array $options options array
     * @return array|false array of results in the same structure as the $attributes array
     */
    public function askFor($attributes, $searchFilter, $options = []) {
        if (array_key_exists("limit", $options) && $options["limit"] == -1) {
            if (!ldap_get_option($this->connection, LDAP_OPT_SIZELIMIT, $options["limit"]) ) {
                $options["limit"] = 0;
            }
        }

        Yii::debug(substitute('Querying LDAP with search filter `{searchFilter}` and base dn `{base}` for the attributes `{attributes}`.', [
            'searchFilter' => $searchFilter,
            'base' => $this->baseDn,
            'attributes' => implode(", ", $attributes),
        ]), __METHOD__);

        $result = @ldap_search($this->connection, $this->baseDn, $searchFilter, $attributes, 0, $options["limit"]);

        $retarr = [];

        if ($result === false) {
            $this->error = 'Search failed: ' . ldap_error($this->connection);
            Yii::debug($this->error, __METHOD__);
            return false;
        }

        if ( ($info = ldap_get_entries($this->connection, $result)) !== false) {
            if($info['count'] != 0) {
                $this->debug[] = Yii::t('auth', 'Retrieving {n} entries.', [
                    'n' => $info['count'],
                ]);

                $retarr['count'] = $info['count'];
                foreach ($attributes as $key => $attr) {
                    if ($info['count'] > 1) {
                        $retarr[$attr] = [];
                        for ($i = 0; $i < $info["count"]; $i++) {
                            array_push($retarr[$attr], $this->get_ldap_attribute($info, $attr, $i));
                        }
                    } else {
                        if (in_array("returnArray", $options, true)) {
                            $retarr[$attr] = [$this->get_ldap_attribute($info, $attr)];
                        } else {
                            $retarr[$attr] = $this->get_ldap_attribute($info, $attr);
                        }
                    }
                }
                return $retarr;
            } else {
                if (array_key_exists("checkAttribute", $options)) {
                    $this->error = Yii::t('auth', 'No result found, check <code>{attribute}</code>.', [
                        'attribute' => $options["checkAttribute"],
                    ]);
                } else {
                    $this->error = Yii::t('auth', 'No result found.');
                }
                Yii::debug($this->error, __METHOD__);
                if (in_array("noError", $options)) {
                    $this->error = null;
                }
                return false;
            }
        } else {
            $this->error = ldap_error($this->connection);
            Yii::debug('Recieving entries failed: ' . $this->error, __METHOD__);
            return false;
        }

    }

    public function get_ldap_attribute($info, $attr, $i=0)
    {

        if (is_array($info) && array_key_exists($i, $info)) {
            if (is_array($info[$i]) && array_key_exists(strtolower($attr), $info[$i])) {
                if (is_array($info[$i][strtolower($attr)]) && array_key_exists(0, $info[$i][strtolower($attr)])) {
                    return $this->postProcess($attr, $info[$i][strtolower($attr)][0]);
                } else {
                    return $this->postProcess($attr, $info[$i][strtolower($attr)]);
                }
            }
        }

        $this->error = Yii::t('auth', 'Attribute <code>{attribute}</code> not existing.', [
            'attribute' => $attr,
        ]);
        Yii::debug($this->error, __METHOD__);
        return false;
    }

    /**
     * Query Groups for the mapping. Populates the [[groups]] array.
     * 
     * @return bool 
     */
    public function query_groups()
    {

        $this->debug[] = Yii::t('auth', 'Querying LDAP with group search filter <code>{searchFilter}</code> and base dn <code>{base}</code> for the group identifier attribute <code>{attribute}</code>.', [
            'searchFilter' => $this->groupSearchFilter,
            'base' => $this->baseDn,
            'attribute' => $this->groupIdentifier,
        ]);

        if (( $ret = $this->askFor(array($this->groupIdentifier), $this->groupSearchFilter, [
                'limit' => 0,
                'checkAttribute' => 'groupSearchFilter',
            ])) !== false)
        {
            if($ret['count'] > 0) {
                $this->success = Yii::t('auth', 'Found {n} LDAP groups.', [
                    'n' => $ret['count'],
                ]);

                $groups = array_combine($ret[$this->groupIdentifier], $ret[$this->groupIdentifier]);
                $this->groups = array_merge($this->groups, $groups);
                return true;
            }
        }
        return false;
    }

    /**
     * Query Users for the user migration.
     * Populates the [[migrateUsers]] array
     *
     * @param array $users array of local users after which the server should be queried.
     * @return bool 
     */
    public function query_users($users)
    {
        $c = 0;
        $i = 0;
        $N = count($users);
        if ($N != 0) {
            $this->debug[] = Yii::t('auth', 'Querying LDAP for existing users with base dn <code>{base}</code> for the attributes <code>{attribute1}</code> and <code>{attribute2}</code>.', [
                'base' => $this->baseDn,
                'attribute1' => $this->uniqueIdentifier,
                'attribute2' => $this->userIdentifier,
            ]);
        }
        foreach ($users as $idFromDb => $usernameFromDb) {
            /*$usernameReal = $this->getRealUsernameByScheme($usernameFromDb, $this->migrateSearchPattern, [
                'domain' => $this->domain,
                'base' => $this->baseDn,
            ]);*/

            $searchFilter = $this->substitute($this->migrateUserSearchFilter, ['username' => $usernameFromDb]);

            $this->debug[] = Yii::t('auth', '{c}/{N}: Querying LDAP for <code>{user}</code> with search filter <code class="show_more">{searchFilter}</code>.', [
                'c' => ++$i,
                'N' => $N,
                'user' => $usernameFromDb,
                'searchFilter' => $searchFilter,
            ]);

            $attributes = array($this->uniqueIdentifier, $this->userIdentifier);
            $ret = $this->askFor($attributes, $searchFilter, [
                'limit' => 0,
                'checkAttribute' => 'migrateUserSearchFilter',
                'noError',
            ]);

            if ($ret !== false) {
                $usernameFromLdap = $ret[$this->userIdentifier];
                $identifier = $ret[$this->uniqueIdentifier];

                $this->addMigrateUser([
                    'id' => $idFromDb,
                    'identifier' => $identifier,
                    'username' => $usernameFromDb,
                ]);

                $c = $c + 1;

                $this->debug[] = Yii::t('auth', 'Found {n} users - taking the first one with <code>{uniqueIdentifier}</code> = <code>{identifier}</code>.', [
                    'n' => $ret['count'],
                    'uniqueIdentifier' => $this->uniqueIdentifier,
                    'identifier' => $identifier,
                ]);
            } else {
                $this->debug[] = Yii::t('auth', 'No user found.');
            }
        }

        if ($c != 0) {
            $this->success = Yii::t('auth', 'Retrieving {n} user entries.', [
                'n' => $c,
            ]);
            return true;
        } else {
            $this->error = 'No result found, check <code>migrateUserSearchFilter</code>.';
            Yii::debug($this->error, __METHOD__);
        }
    }

    /**
     * @param string $attribute the attribute currently being validated
     * @param mixed $params the value of the "params" given in the rule
     * @param \yii\validators\InlineValidator $validator related InlineValidator instance.
     * This parameter is available since version 2.0.11.
     */
    public function getAllLdapGroups ($attribute, $params, $validator)
    {
        if ($this->bindLdap($this->query_username, $this->query_password) !== false) {
            if($this->query_groups()) {
                return;
            }
        }

        $this->addError('query_password', \Yii::t('auth', 'Incorrect username or password.'));
    }

    /**
     * @param string $attribute the attribute currently being validated
     * @param mixed $params the value of the "params" given in the rule
     * @param \yii\validators\InlineValidator $validator related InlineValidator instance.
     * This parameter is available since version 2.0.11.
     */
    public function queryUsers ($attribute, $params, $validator)
    {
        $localUsers = $this->findMigratableUsers();

        if (count($localUsers) !== 0) {
            if ($this->bindLdap($this->query_username, $this->query_password) !== false) {
                $this->query_users($localUsers);
                if(empty($this->migrateUsers)) {
                    $this->addError($attribute, \Yii::t('auth', 'Found no usernames matching search pattern.'));
                }
            } else {
                $this->addError('query_password', \Yii::t('auth', 'Incorrect username or password.'));
            }
        }
    }

    /**
     * Closes the currently active LDAP connection.
     * It does nothing if the connection is already closed.
     */
    public function close()
    {
        if ($this->connection !== null) {
            Yii::debug('Closing LDAP connection: ' . $this->domain, __METHOD__);
            @ldap_close($this->connection);
            $this->connection = null;
        }
    }
}
