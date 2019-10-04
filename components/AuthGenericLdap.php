<?php

namespace app\components;
 
use Yii;
use yii\base\Component;
use app\models\User;
use yii\base\InvalidConfigException;

/**
 * AuthGenericLdap represents a connection to an generic LDAP directory.
 *
 * @property bool $isActive Whether the LDAP connection is established. This property is read-only.
 */
class AuthGenericLdap extends \app\models\Auth
{

    const SCENARIO_QUERY_GROUPS = 'query_groups';
    const SCENARIO_QUERY_USERS = 'query_users';
    const SCENARIO_AUTH_TEST = 'auth_test';

    /**
     * @const int extended error output
     */
    const LDAP_OPT_DIAGNOSTIC_MESSAGE = 0x0032;

    /**
     * @const string Bind directly to the LDAP using the username from login,
     */
    const BIND_BY_USERNAME = 'bind_by_username';

    /**
     * @const string Bind via a given bind user to the LDAP, then retrieve the bind attribute of the login user,
     * then bind again using this attribute to authentication the login user,
     */
    const BIND_BY_BINDUSER = 'bind_by_binduser';

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
        'distinguishedName',
        'userPrincipalName', //AD
        'cn',
        'name',
        'mail',
        'objectGUID', //AD
        'uid', //LDAP
        'gidNumber', //LDAP
    ];

    /**
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
     * @see searchFilter
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
     * @see searchFilter
     */
    public $bindScheme = '{username}@{domain}';

    /**
     * @var string The search filter to query the LDAP for information on the current bind user.
     * 
     * Unfortuately, we cannot use LDAPs extended operation for this (`ldap_exop()` with `LDAP_EXOP_WHO_AM_I`), since it needs PHP `>=7.2.0`.
     * `{username}` is the string corresponding to `{username}` extracted from [[$loginScheme]].
     * 
     * Examples:
     *
     * ```php
     * $searchFilter = '(sAMAccountName={username})';  // search for entries matching the sAMAccountName
     * $searchFilter = '(userPrincipalName={username}@foo)'; // search for entries matching the userPrincipalName to be appended by "@foo"
     * $searchFilter = '(userPrincipalName={username}@{domain})'; // search for entries matching the userPrincipalName to be appended by "@{domain}", where {domain} is replaced with the value given in the configuration
     * $searchFilter = '(dn=cn={username},ou=People,dc=test,dc=local)';    // search for entries matching the distinguished name. Instead of "dc=test,dc=local", one could have also used {base}.
     * ```
     * 
     * The placeholders that are replaced by the values given are: `{domain}` with [[domain]], `{netbiosDomain}`  with [[netbiosDomain]], `{base}` with with [[base]].
     *
     * @see https://www.php.net/manual/en/function.ldap-exop.php 
     * @see loginScheme
     */
    public $searchFilter = '(uid={username})';

    /**
     * @var string The search filter to query the LDAP for all group objects
     */
    public $groupSearchFilter = '(objectClass=posixGroup)';

    /**
     * @var array Array of common search filters to use for group probing for the select list of [[groupSearchFilter]].
     */
    public $groupSearchFilterList = [
        '(objectCategory=group)' => '(objectCategory=group)',
        '(objectClass=posixGroup)' => '(objectClass=posixGroup)', //LDAP
        '(& (objectCategory=group) (sAMAccountType=268435456))' => '(& (objectCategory=group) (sAMAccountType=268435456=GROUP_OBJECT))',
        '(& (objectCategory=group) (sAMAccountType=536870912))' => '(& (objectCategory=group) (sAMAccountType=536870912=ALIAS_OBJECT))',
        '(& (objectCategory=group) (sAMAccountType=268435457))' => '(& (objectCategory=group) (sAMAccountType=268435457=NON_SECURITY_GROUP_OBJECT))',
    ];

    /**
     * @var array Array of LDAP groups for the select list for the role mapping.
     */
    public $groups = [];

    /**
     * @var string The search filter to query the LDAP for user objects
     * The placeholders that are replaced by the values given are: {domain}, {netbiosDomain}, {base}, {userIdentifier}.
     */
    public $migrateUserSearchFilter = '(& (objectCategory=person) ({userIdentifier}={username}) )';

    /**
     * @var string A search scheme for the username of local users to migrate
     */
    public $migrateSearchScheme = '{username}';

    /**
     * @var array Array of LDAP users for the select list of the migration form.
     */
    public $migrateUsers = [];

    public $query_username;
    public $query_password;

    public $migrate = [];

    /**
     * @var int method of authentication
     */
    public $method = self::BIND_BY_USERNAME;

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
    public $bindAttribute = 'uid';

    /**
     * @var string search filter to search for the login user entry in the LDAP
     */
    public $bindSearchFilter = '(& (objectCategory=person) ({loginAttribute}={username}) )';


    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->domain !== '') {
            if ($this->ldap_uri === '') {
                $this->ldap_uri = $this->ldap_scheme . '://' . $this->domain . ':' . $this->ldap_port;
            }
            if ($this->base === '') {
                $this->base = "dc=" . implode(",dc=", explode(".", $this->domain));
            }
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
            [['domain'], 'required', 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_QUERY_GROUPS]],

            ['mapping', 'filter', 'filter' => [$this, 'processMapping'], 'on' => self::SCENARIO_DEFAULT],

            [['domain', 'ldap_uri', 'loginScheme', 'bindScheme', 'searchFilter', 'groupIdentifier', 'groupSearchFilter', 'query_username', 'query_password'], 'safe', 'on' => self::SCENARIO_QUERY_GROUPS],

            [['migrateSearchScheme', 'userIdentifier', 'migrateUserSearchFilter', 'query_username', 'query_password'], 'safe', 'on' => self::SCENARIO_QUERY_USERS],

            [
                ['query_username', 'query_password'],
                'required',
                'when' => function($model) {return $model->scenario == self::SCENARIO_QUERY_GROUPS;},
                'whenClient' => "function (attribute, value) {
                    return $('#ldap-scenario').val() == 'query_groups';
                }",
                'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_QUERY_GROUPS]
            ],
            [
                'query_password',
                'getAllLdapGroups',
                'when' => function($model) {return !empty($model->domain);},
                'on' => self::SCENARIO_QUERY_GROUPS
            ],

            [['query_username', 'query_password'], 'safe', 'on' => self::SCENARIO_AUTH_TEST],
            [['query_username', 'query_password'], 'required', 'on' => self::SCENARIO_AUTH_TEST],
            ['query_password', 'authenticateTest', 'on' => self::SCENARIO_AUTH_TEST],

            [['query_username', 'query_password'], 'required',
                'when' => function($model) {return $model->scenario == self::SCENARIO_QUERY_USERS;},
                'whenClient' => "function (attribute, value) {
                    return $('#ldap-scenario').val() == 'query_users';
                }",
                'on' => [self::SCENARIO_MIGRATE, self::SCENARIO_QUERY_USERS]
            ],
            ['query_password', 'queryUsers', 'on' => self::SCENARIO_QUERY_USERS],

            [['migrate'], 'safe', 'on' => self::SCENARIO_MIGRATE],
            [['migrate'], 'required', 'on' => self::SCENARIO_MIGRATE],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_DEFAULT] = array_merge($scenarios[self::SCENARIO_DEFAULT], ['domain', 'ldap_uri', 'loginScheme', 'bindScheme', 'searchFilter', 'groupIdentifier', 'groupSearchFilter', 'mapping', 'uniqueIdentifier', 'bindAttribute']);
        $scenarios[self::SCENARIO_QUERY_GROUPS] = ['domain', 'ldap_uri', 'loginScheme', 'bindScheme', 'searchFilter', 'groupIdentifier', 'groupSearchFilter', 'query_username', 'query_password'];
        $scenarios[self::SCENARIO_AUTH_TEST] = ['query_username', 'query_password'];
        $scenarios[self::SCENARIO_QUERY_USERS] = ['migrateSearchScheme', 'migrateUserSearchFilter', 'userIdentifier', 'query_username', 'query_password'];
        $scenarios[self::SCENARIO_MIGRATE] = ['migrate'];
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'domain' => Yii::t('auth', 'Domain'),
            'base' => Yii::t('auth', 'Base DN'),
            'ldap_uri' => Yii::t('auth', 'LDAP URI'),
            'ldap_port' => Yii::t('auth', 'LDAP Port'),
            'ldap_scheme' => Yii::t('auth', 'LDAP Scheme'),
            'ldap_options' => Yii::t('auth', 'LDAP Options'),
            'loginScheme' => \Yii::t('auth', 'Login Scheme'),
            'bindScheme' => \Yii::t('auth', 'Bind Scheme'),
            'searchFilter' => \Yii::t('auth', 'Search Filter'),
            'uniqueIdentifier' => \Yii::t('auth', 'User Identifier Attribute'),
            'groupIdentifier' => \Yii::t('auth', 'Group Identifier Attribute'),
            'groupSearchFilter' => \Yii::t('auth', 'Group Search Filter'),
            'mapping' => \Yii::t('auth', 'Group Mapping'),
            'query_login' => $this->scenario == self::SCENARIO_AUTH_TEST
                ? \Yii::t('auth', 'Test Credentials')
                : \Yii::t('auth', 'Query Credentials'),
            'query_username' => \Yii::t('auth', 'Username'),
            'query_password' => \Yii::t('auth', 'Password'),
            'bindAttribute' => \Yii::t('auth', 'Bind Attribute'),
            'loginAttribute' => \Yii::t('auth', 'Login Attribute'),
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
            'loginScheme' => \Yii::t('auth', 'The pattern to test the given login credentials against a login over LDAP will only be performed if the given username matches the provided pattern. This is used to manage multiple LDAPs. {username} is extracted from the username given in the login form. Later in the authentication {username} is replaced by the extracted one from here (see <code>bindScheme</code> and <code>searchFilter</code>).<br>Examples:<br><ul><li><code>{username}</code>: no special testing, all usernames provided are authenticated against the LDAP.</li><li><code>{username}@foo</code>: only usernames ending with @foo are considered and authenticated agaist the LDAP.</li><li><code>{username}@{domain}</code>: only usernames ending with @{domain} are considered and authenticated agaist the LDAP. {domain} is replaced with the given <code>domain</code> configuration variable.</li><li><code>foo\{username}</code>: only usernames starting with foo\ are considered and authenticated agaist the LDAP.</li></ul>The placeholders that are replaced by the values given are: <code>{domain}</code>, <code>{netbiosDomain}</code>, <code>{base}</code>.'),
            'bindScheme' => \Yii::t('auth', 'TODO'),
            'searchFilter' => \Yii::t('auth', 'TODO'),
            'groupIdentifier' => \Yii::t('auth', 'TODO'),
            'groupSearchFilter' => \Yii::t('auth', 'TODO'),
            'mapping' => \Yii::t('auth', 'The direct assignment of LDAP groups to user roles. You can map multiple LDAP groups to the same role. Goups need not to be assigned to all roles.'),
            'query_login' => $this->scenario == self::SCENARIO_AUTH_TEST
                ? \Yii::t('auth', 'Username and password to login to the LDAP servers. Login credentials are not saved anywhere.')
                : \Yii::t('auth', 'Username and password to query the LDAP servers given above for group names. This information is only needed to specify the group mapping. Login credentials are not saved anywhere.'),
            'bindAttribute' => \Yii::t('auth', 'TODO'),
            'loginAttribute' => \Yii::t('auth', 'TODO'),
        ]);
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
     *
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
     * Decides whether the username provided by the user matches the pattern to authenticate.
     * @param string $username the username that was provided to the login form by the user attempting to login
     * @param string $scheme the login scheme
     * @param array $substitutions array of substitutions
     *
     * @return bool whether the provided username matches the pattern or not
     */
    public function getRealUsernameByScheme($username, $scheme, $substitutions) {
        //$regex = '([^\"\/\\\[\]\:\;\|\=\,\+\*\?\<\>]+)';
        $regex = '(.+)';
        $pattern = substitute($scheme, array_merge($substitutions, [
            'username' => '@@@@',
        ]));
        $pattern = preg_quote($pattern, '/');
        $pattern = str_replace('@@@@', $regex, $pattern);

        preg_match('/'.$pattern.'/', $username, $matches);

        if (array_key_exists(1, $matches)) {
            return $matches[1];
        }
        return false;
    }

    /**
     * Decides whether the username provided by the user matches the pattern to authenticate over LDAP.
     * @param string username the username that was provided to the login form by the user attempting to login
     *
     * @return bool whether the provided username matches the pattern or not
     */
    public function getRealUsername($username)
    {
        return $this->getRealUsernameByScheme($username, $this->loginScheme, [
            'domain' => $this->domain,
            'base' => $this->base,
        ]);
    }

    /**
     * Contructs the username to bind to the LDAP.
     * @param string username the real username that was extracted from [[getRealUsername()]]
     *
     * @return string the bind username
     */
    public function getBindUsername($username)
    {
        # if the method is "bind by binduser" then first bind with that user to retrieve
        # the bind username of the login user.
        if ($this->method == self::BIND_BY_BINDUSER) {
            if (($username = $this->getBindAttribute($username)) !== false) {
                return $username;
            } else {
                return false;
            }
        }

        $username = $this->getSubstitutedBindUsername($username);
        Yii::debug('Username matches LDAP::loginScheme. Proceeding with bind username: ' . $username, __METHOD__);
        $this->debug[] = Yii::t('auth', 'Proceeding with bind username <code>{bindUser}</code> according to bindScheme <code>{bindScheme}</code>.', [
            'bindUser' => $username,
            'bindScheme' => $this->bindScheme,
        ]);
        return $username;
    }

    public function getSubstitutedBindUsername($username)
    {
        return substitute($this->bindScheme, [
            'domain' => $this->domain,
            'base' => $this->base,
            'username' => $username,
        ]);
    }

    /**
     * Retrieves the group names from a list of distinguishedName's
     * @param array $dns set of distinguishedName's of groups
     *
     * @return array the resolved human readable group names, according to [[groupIdentifier]].
     */
    public function getGroupNames($dns)
    {
        $filter = '(| ';
        for ($i = 0; $i < $dns["count"]; $i++) {
            $filter .= "(distinguishedName=$dns[$i]) ";
        }
        $filter .= ')';

        if (!ldap_get_option($this->connection, LDAP_OPT_SIZELIMIT, $limit) ) {
            $limit = 0;
        }
        $r = ldap_search($this->connection, $this->base, $filter, array($this->groupIdentifier), 0, $limit);
        $info = ldap_get_entries($this->connection, $r);
        $groups = array();  
        for ($i = 0; $i < $info["count"]; $i++) {
            array_push($groups, $info[$i][strtolower($this->groupIdentifier)][0]);
        }
        return $groups;
    }

    /**
     * Determines the highest possible roles for a set of given groups
     * 
     * @param array groups set of LDAP groups
     * @return mixed the highest role according to [[roleOrder]] or the first element from the roles
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
     * Getter for the searchFilter with substituted values
     * @return string substituted string
     */
    public function getSubstitutedSearchFilter($username)
    {
        return substitute($this->searchFilter, [
            'domain' => $this->domain,
            'base' => $this->base,
            'username' => $username,
            'bindAttribute' => $this->bindAttribute,
        ]);
    }

    /**
     * Getter for the migrateSearchFilter with substituted values
     * @return string substituted string
     */
    public function getSubstitutedMigrateSearchFilter($username)
    {
        return substitute($this->migrateUserSearchFilter, [
            'domain' => $this->domain,
            'base' => $this->base,
            'userIdentifier' => $this->userIdentifier,
            'username' => $username,
        ]);
    }

    /**
     * Getter for the migrateSearchScheme with substituted values
     * @return string substituted string
     */
    public function getSubstitutedMigrateSearchScheme($username)
    {
        return substitute($this->migrateSearchScheme, [
            'domain' => $this->domain,
            'base' => $this->base,
            'username' => $username,
        ]);
    }

    /**
     * Getter for the bindSearchFilter with substituted values
     * @return string substituted string
     */
    public function getSubstitutedBindSearchFilter($username)
    {
        return substitute($this->bindSearchFilter, [
            'userIdentifier' => $this->userIdentifier,
            'loginAttribute' => $this->loginAttribute,
            'domain' => $this->domain,
            'base' => $this->base,
            'username' => $username,
        ]);
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

        Yii::debug('Opening LDAP connection: ' . $this->ldap_uri, __METHOD__);
        //$this->debug[] = 'Opening LDAP connection: ' . $this->ldap_uri;
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
     * Setup the bind to the LDAP.
     * 
     * @param string $username the username given from the login form
     * @param string $password the password given from the login form
     * @return false|string bind failure or the username
     */
    public function CheckAndBind($username, $password)
    {
        $this->init();
        if ($user = $this->getRealUsername($username)) {
            $this->debug[] = Yii::t('auth', 'Username <code>{username}</code> matches loginScheme <code>{loginScheme}</code>.', [
                'username' => $username,
                'loginScheme' => $this->loginScheme,
            ]);

            if (($bindUser = $this->getBindUsername($user)) !== false) {
                return $this->bindLdap($bindUser, $password);
            } else {
                return false;
            }
        } else {
            $this->error = Yii::t('auth', 'Username <code>{username}</code> does not match loginScheme <code>{loginScheme}</code> of authentication method <code>{name}</code>.', [
                'username' => $username,
                'loginScheme' => $this->loginScheme,
                'name' => $this->name,
            ]);
            Yii::debug($this->error, __METHOD__);
            return false;
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
        $this->bind = @ldap_bind($this->connection, $username, $password);

        if ($this->bind) {
            Yii::debug('Bind successful', __METHOD__);
            $this->debug[] = Yii::t('auth', 'Bind with username <code>{username}</code> successful', [
                'username' => $username,
            ]);
            return $username;
        } else {
            $this->error = Yii::t('auth', 'Bind with username <code>{username}</code> failed: {error}', [
                'username' => $username,
                'error' => ldap_error($this->connection),
            ]);
            ldap_get_option($this->connection, AuthGenericLdap::LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error);
            if (!empty($extended_error)) {
                $this->error .= ", Detailed error message: " . $extended_error;
            }
            Yii::debug($this->error, __METHOD__);
            $this->close();
            return false;
        }
    }

    /**
     * Finds the attribtue in the LDAP directory for the bind with the login user.
     * 
     * @return false|string bind failure or the username
     */
    public function getBindAttribute($username)
    {
        if (($user = $this->bindLdap($this->bindUsername, $this->bindPassword)) !== false) {
            $searchFilter = $this->getSubstitutedBindSearchFilter($username);

            $this->debug[] = Yii::t('auth', 'Querying LDAP with search filter <code>{searchFilter}</code> and base dn <code>{base}</code> for the bind attribute <code>{attribute}</code>.', [
                'searchFilter' => $searchFilter,
                'base' => $this->base,
                'attribute' => $this->bindAttribute,
            ]);

            $result = @ldap_search($this->connection, $this->base, $searchFilter, array($this->bindAttribute), 0, 1);

            if ($result === false) {
                $this->error = 'Search failed: ' . ldap_error($this->connection);
                Yii::debug($this->error, __METHOD__);
                return false;
            }

            if ($userInfo = ldap_get_entries($this->connection, $result)) {
                if($userInfo['count'] != 0) {

                    $this->debug[] = Yii::t('auth', 'Retrieving {n} user entries.', [
                        'n' => $userInfo['count'],
                    ]);
                    $username = $userInfo[0][strtolower($this->bindAttribute)][0];

                    // convert binary data to hex if the identifier is objectGUID
                    if ($this->bindAttribute == 'objectGUID') {
                        $username = $this->convertGUIDToHex($username);
                    }

                    $this->debug[] = Yii::t('auth', 'Bind attribute <code>{attribute}</code> is <code>{username}</code>.', [
                        'attribute' =>$this->bindAttribute,
                        'username' => $username,
                    ]);
                    $this->searchFilter = '({bindAttribute}={username})';
                    return $username;
                } else {
                    $this->error = 'No result found, check <code>bindSearchFilter</code>.';
                    Yii::debug($this->error, __METHOD__);
                    return false;
                }
            } else {
                $this->error = ldap_error($this->connection);
                Yii::debug('Recieving entries failed: ' . $this->error, __METHOD__);
                return false;
            }
        } else {
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

        if (($user = $this->CheckAndBind($username, $password)) !== false) {

            $searchFilter = $this->getSubstitutedSearchFilter($user);

            $this->debug[] = Yii::t('auth', 'Querying LDAP with search filter <code>{searchFilter}</code> and base dn <code>{base}</code> for the attribute <code>{attribute}</code>.', [
                'searchFilter' => $searchFilter,
                'base' => $this->base,
                'attribute' => $this->uniqueIdentifier,
            ]);
            $result = @ldap_search($this->connection, $this->base, $searchFilter, array($this->uniqueIdentifier, 'memberOf'), 0, 1);

            if ($result === false) {
                $this->error = 'Search failed: ' . ldap_error($this->connection);
                Yii::debug($this->error, __METHOD__);
                return false;
            }

            if ($userInfo = ldap_get_entries($this->connection, $result)) {
                if($userInfo['count'] != 0) {

                    $this->debug[] = Yii::t('auth', 'Retrieving {n} user entries.', [
                        'n' => $userInfo['count'],
                    ]);
                    $this->identifier = $userInfo[0][strtolower($this->uniqueIdentifier)][0];

                    // convert binary data to hex if the identifier is objectGUID
                    if ($this->uniqueIdentifier == 'objectGUID') {
                        $this->identifier = $this->convertGUIDToHex($this->identifier);
                    }

                    $this->debug[] = Yii::t('auth', 'User identifier set to <code>{identifier}</code>.', [
                        'identifier' => $this->identifier
                    ]);
                    $memberOf = $userInfo[0][strtolower('memberOf')];
                    $groups = $this->getGroupNames($memberOf);

                    if ($this->role = $this->determineRole($groups)) {
                        $this->close();
                        $this->debug[] = Yii::t('auth', 'User role set to <code>{role}</code>.', [
                            'role' => $this->role
                        ]);

                        $this->success = Yii::t('auth', 'Authentication was successful.');
                        Yii::debug('role=' . $this->role . ', identifier=' . $this->identifier, __METHOD__);
                        Yii::debug('Authentication was successful.', __METHOD__);
                        return true;
                    } else {
                        $this->debug[] = Yii::t('auth', 'User group membership: <code>{membership}</code>.', [
                            'membership' => json_encode($groups),
                        ]);
                        $this->debug[] = Yii::t('auth', 'Mapping: <code>{mapping}</code>.', [
                            'mapping' => json_encode($this->mapping),
                        ]);
                        $this->error = 'No role found, check <code>roleOrder</code> and <code>mapping</code>.';
                        Yii::debug($this->error, __METHOD__);
                        $this->close();
                        return false;
                    }
                } else {
                    $this->error = 'No result found, check <code>searchFilter</code>.';
                    Yii::debug($this->error, __METHOD__);
                    return false;
                }
            } else {
                $this->error = ldap_error($this->connection);
                Yii::debug('Recieving entries failed: ' . $this->error, __METHOD__);
                return false;
            }
        } else {
            return false;
        }
    }

    function convertGUIDToHex($guid)
    {
        $unpacked = unpack('Va/v2b/n2c/Nd', $guid);
        return strtolower(sprintf('%08X-%04X-%04X-%04X-%04X%08X', $unpacked['a'], $unpacked['b1'], $unpacked['b2'], $unpacked['c1'], $unpacked['c2'], $unpacked['d']));
    }

    /**
     * Query Groups for the mapping
     * 
     * @return bool 
     */
    public function query_groups()
    {

        $this->debug[] = Yii::t('auth', 'Querying LDAP with search filter <code>{searchFilter}</code> and base dn <code>{base}</code> for the attribute <code>{attribute}</code>.', [
            'searchFilter' => $this->groupSearchFilter,
            'base' => $this->base,
            'attribute' => $this->groupIdentifier,
        ]);

        $result = @ldap_search($this->connection, $this->base, $this->groupSearchFilter, array($this->groupIdentifier), 0, 0);

        if ($result === false) {
            $this->error = 'Search failed:' . ldap_error($this->connection);
            Yii::debug($this->error, __METHOD__);
            return false;
        }

        if ($groupInfo = ldap_get_entries($this->connection, $result)) {
            if($groupInfo['count'] != 0) {
                $this->success = 'Retrieving ' . $groupInfo['count'] . ' group entries.';
                //$groupName = $groupInfo[0][strtolower($this->groupIdentifier)];
                $groups = array_column($groupInfo, strtolower($this->groupIdentifier));
                $groups = array_column($groups, 0);

                // convert binary data to hex if the identifier is objectGUID
                if ($this->groupIdentifier == 'objectGUID') {
                    array_walk($groups, function(&$group){
                        $group = $this->convertGUIDToHex($group);
                    });
                }
                $groups = array_combine($groups, $groups);
                //$this->debug[] = print_r($groups, true);
                //var_dump($groups);
                $this->groups = $groups;
                return true;
            } else {
                $this->error = 'No result found, check <code>groupSearchFilter</code>.';
                Yii::debug($this->error, __METHOD__);
                return false;
            }
        } else {
            $this->error = 'recieving group entries failed: ' . ldap_error($this->connection);
            Yii::debug($this->error, __METHOD__);
            return false;
        }
    }

    /**
     * Query Users for the user migration
     * @param array $users array of local users after which the server should be 
     * queried.
     * 
     * @return bool 
     */
    public function query_users($users)
    {
        $c = 0;
        $N = count($users);
        if ($N != 0) {
            $this->debug[] = Yii::t('auth', 'Querying LDAP for existing local users with base dn <code>{base}</code> for the attributes <code>{attribute1}</code> and <code>{attribute2}</code>.', [
                'base' => $this->base,
                'attribute1' => $this->uniqueIdentifier,
                'attribute2' => $this->userIdentifier,
            ]);
        }
        foreach ($users as $key => $usernameFromDb) {
            $usernameReal = $this->getRealUsernameByScheme($usernameFromDb, $this->migrateSearchScheme, [
                'domain' => $this->domain,
                'base' => $this->base,
            ]);

            $searchFilter = $this->getSubstitutedMigrateSearchFilter($usernameReal);

            $this->debug[] = Yii::t('auth', '{c}/{N}: Querying LDAP for <code>{user}</code> with search filter <code class="show_more">{searchFilter}</code>.', [
                'c' => $key+1,
                'N' => $N,
                'user' => $usernameFromDb,
                'searchFilter' => $searchFilter,
            ]);

            $result = @ldap_search($this->connection, $this->base, $searchFilter, array($this->uniqueIdentifier, $this->userIdentifier), 0, 0);

            if ($result === false) {
                $this->error = 'search failed:' . ldap_error($this->connection);
                Yii::debug($this->error, __METHOD__);
                return false;
            }

            if ($userInfo = ldap_get_entries($this->connection, $result)) {
                if($userInfo['count'] != 0) {
                    $usernameFromLdap = $userInfo[0][strtolower($this->userIdentifier)][0];
                    $identifier = $userInfo[0][strtolower($this->uniqueIdentifier)][0];

                    // convert binary data to hex if the identifier is objectGUID
                    if ($this->userIdentifier == 'objectGUID') {
                        $usernameFromLdap = $this->convertGUIDToHex($usernameFromLdap);
                    }

                    if ($this->uniqueIdentifier == 'objectGUID') {
                        $identifier = $this->convertGUIDToHex($identifier);
                    }

                    //$users = array_combine($identifier, $users);
                    $this->migrateUsers[$identifier] = $usernameFromDb;
                    $c = $c + 1;

                    $this->debug[] = Yii::t('auth', 'Found {n} users - taking the first one with <code>{uniqueIdentifier}={identifier}</code>.', [
                        'n' => $userInfo['count'],
                        'uniqueIdentifier' => $this->uniqueIdentifier,
                        'identifier' => $identifier,
                    ]);
                } else {
                    $this->debug[] = Yii::t('auth', 'No user found.');
                }
            } else {
                $this->error = 'recieving user entries failed: ' . ldap_error($this->connection);
                Yii::debug($this->error, __METHOD__);
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
        if ($this->bindLdap($this->query_username, $this->query_password)) {
            if($this->query_groups()) {
                return;
            }
        }
        $this->addError($attribute, \Yii::t('auth', 'Incorrect username or password.'));
    }

    /**
     * @param string $attribute the attribute currently being validated
     * @param mixed $params the value of the "params" given in the rule
     * @param \yii\validators\InlineValidator $validator related InlineValidator instance.
     * This parameter is available since version 2.0.11.
     */
    public function authenticateTest ($attribute, $params, $validator)
    {
        if ($this->authenticate($this->query_username, $this->query_password)) {
            return;
        }
        $this->addError($attribute, \Yii::t('auth', 'Login failed.'));
    }

    /**
     * @param string $attribute the attribute currently being validated
     * @param mixed $params the value of the "params" given in the rule
     * @param \yii\validators\InlineValidator $validator related InlineValidator instance.
     * This parameter is available since version 2.0.11.
     */
    public function queryUsers ($attribute, $params, $validator)
    {
        // search for local usernames matching [[migrateSearchScheme]]
        $models = User::find()
            ->where(['identifier' => null])
            ->andWhere(['type' => '0'])
            ->andWhere(['not', ['id' => 1]])
            ->andWhere(['like', 'username', $this->getSubstitutedMigrateSearchScheme('')])
            ->all();

        $localUsers = array_column($models, 'username');
        $c = count($localUsers);

        if ($c == 0) {
            $this->error = Yii::t('auth', 'Found no local usernames matching search pattern <code>{migrateSearchScheme}</code>.', [
                'migrateSearchScheme' => $this->getSubstitutedMigrateSearchScheme('{username}'),
            ]);
            Yii::debug($this->error, __METHOD__);
            return false;
        } else {
            $this->debug[] = Yii::t('auth', 'Found {n} local usernames matching search pattern for local usernames <code>{migrateSearchScheme}</code>: <span class="show_more">{users}</span>', [
                'n' => $c,
                'migrateSearchScheme' => $this->getSubstitutedMigrateSearchScheme('{username}'),
                'users' => $c == 0 ? '' : '<code>' . implode('</code>, <code>', $localUsers) . '</code>',
            ]);
        }
        unset($models);

        if ($this->bindLdap($this->query_username, $this->query_password)) {
            if($this->query_users($localUsers)) {
                return;
            }
        }
        $this->addError($attribute, \Yii::t('auth', 'Incorrect username or password.'));
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
        }
    }
}
