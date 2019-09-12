<?php

namespace app\components;
 
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Ad represents a connection to a Active Directory  via LDAP.
 *
 * @property bool $isActive Whether the AD connection is established. This property is read-only.
 */
class Ad extends \app\models\Auth
{

    /**
     * @const int extended error output
     */
    const LDAP_OPT_DIAGNOSTIC_MESSAGE = 0x0032;

    public $ldap_uri = '';
    public $ldap_scheme = 'ldap';
    public $ldap_port = 389;
    public $domain = '';
    public $netbiosDomain = '';

    public $ldap_options = [
        LDAP_OPT_PROTOCOL_VERSION => 3,
        LDAP_OPT_REFERRALS => 0,
        LDAP_OPT_NETWORK_TIMEOUT => 5,
    ];
    public $base = '';

    /**
     * @inheritdoc
     */
    public $class = 'app\components\Ad';

    /**
     * @inheritdoc
     */
    public $type = \app\models\Auth::ACTIVE_DIRECTORY;

    /**
     * @inheritdoc
     */
    public $view = 'view_ad';

    /**
     * @inheritdoc
     */
    public $form = '_form_ad';

    /**
     * @inheritdoc
     */
    public $name = 'AD';

    /**
     * @inheritdoc
     */
    public $description = 'Active Directory Authentication Method';


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
     * membership such that he can be assiciated to multiple roles, this array determines the
     * associated role. The user recieves the highest possible role.
     */
    public $roleOrder = [
        'admin',
        'teacher'
    ];

    public $connection;
    public $bind;

    /**
     * @var array key value pairs for mapping of AD groups (defaultly by sAMAccountName) to roles
     * @see [[groupIdentifier]]
     * 
     * Example:
     *  $mapping = [
     *      'AD-Admin-Group'            => 'admin'
     *      'AD-Teacher-Group'          => 'teacher'
     *      'Another-AD-Teacher-Group'  => 'teacher'
     *  ];
     * 
     * For the example above, if a user is in multiple groups appearing in the mapping, the highest
     * role according to [[roleOrder]] is taken for this user. Multiple AD groups can be mapped to the
     * same role. AD groups can be given in their sAMAccountName or another arbitrary identifier.
     */
    public $mapping = [];

    /**
     * @var string A unique identifier across the Active Directory (that never changes)
     * "The GUID is unique across the enterprise and anywhere else."
     * @see https://blogs.msdn.microsoft.com/openspecification/2009/07/10/understanding-unique-attributes-in-active-directory/
     */
    public $uniqueIdentifier = 'objectGUID';

    /**
     * @var string A (unique) identifier across the Active Directory for the AD groups for [[mapping]]. This
     * should be unique, as that it is used to identify the group membership.
     * The sAMAccountName may not be unique "across the enterprise and anywhere else", but it is human readable.
     * @see [[mapping]]
     */
    public $groupIdentifier = 'sAMAccountName';

    /**
     * @var string The pattern to test the given login credentials against
     * A login over AD will only be performed if the given username matches the provided
     * pattern. This is used to manage multiple ADs. {username} is extracted from the username given in the
     * login form. Later in the authentication {username} is replaced by the extracted one from here.
     * @see [[bindScheme]]
     * @see [[searchFilter]]
     * 
     * Examples:
     *  $loginScheme = '{username}';   // no special testing, all usernames provided are authenticated against the AD.
     *  $loginScheme = '{username}@foo';   // only usernames ending with @foo are considered and authenticated agaist the AD.
     *  $loginScheme = '{username}@{domain}';   // only usernames ending with @{domain} are considered and authenticated agaist the AD. {domain} is replaced with the given [[domain]] configuration variable.
     *  $loginScheme = 'foo\{username}';   // only usernames starting with foo\ are considered and authenticated agaist the AD.
     *
     * The placeholders that are replaced by the values given are: {domain}, {netbiosDomain}, {base}.
     */
    public $loginScheme = '{username}';

    /**
     * @var string The pattern to build the login credentials for the bind to the AD.
     * {username} is the string corresponding to {username} extracted from [[$loginScheme]].
     * 
     * Examples:
     *  $bindScheme = '{username}';       // no special altering, the username is taken as it is.
     *  $bindScheme = '{username}@foo';   // the username is appended with "@foo" for authentication
     *  $bindScheme = '{username}@{domain}';   // the username is appended with "@{domain}", where {domain} is replaced with the value given in the configuration
     *  $bindScheme = 'cn={username},ou=People,dc=test,dc=local';   // a distinguished name is built out of the provided username. Instead of "dc=test,dc=local", one could have also used {base}.
     *  $bindScheme = 'foo\{username}';   // the username is prepended with "foo\" for authentication
     * 
     * The placeholders that are replaced by the values given are: {domain}, {netbiosDomain}, {base}.
     */
    public $bindScheme = '{username}@{domain}';

    /**
     * @var string The search filter to query the AD for information on the current bind user. Unfortuately, we cannot use
     * LDAPs extended operation for this (ldap_exop() with LDAP_EXOP_WHO_AM_I), since it needs PHP >=7.2.0.
     * @see https://www.php.net/manual/en/function.ldap-exop.php 
     * {username} is the string corresponding to {username} extracted from [[$loginScheme]].
     * 
     * Examples:
     *  $searchFilter = '(sAMAccountName={username})';  // search for entries matching the sAMAccountName
     *  $searchFilter = '(userPrincipalName={username}@foo)'; // search for entries matching the userPrincipalName to be appended by "@foo"
     *  $searchFilter = '(userPrincipalName={username}@{domain})'; // search for entries matching the userPrincipalName to be appended by "@{domain}", where {domain} is replaced with the value given in the configuration
     *  $searchFilter = '(dn=cn={username},ou=People,dc=test,dc=local)';    // search for entries matching the distinguished name. Instead of "dc=test,dc=local", one could have also used {base}.
     * 
     * The placeholders that are replaced by the values given are: {domain}, {netbiosDomain}, {base}.
     */
    public $searchFilter = '(sAMAccountName={username})';

    /**
     * @var string The search filter to query the AD for all group objects
     */
    public $groupSearchFilter = '(objectCategory=group)';

    /**
     * @var array Array of AD groups for the select list for the role mapping.
     */
    public $groups = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->domain !== '') {
            if ($this->netbiosDomain === '') {
                $this->netbiosDomain = substr($this->domain, 0, strrpos($this->domain, '.'));
            }
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
            [['domain', 'ldap_uri', 'loginScheme', 'bindScheme', 'searchFilter'], 'safe'],
            [['domain'], 'required'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'domain' => Yii::t('auth', 'Domain'),
            'netbiosDomain' => Yii::t('auth', 'Netbios Domain'),
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
            'mapping' => \Yii::t('auth', 'Group Mapping'),
            'query_login' => \Yii::t('auth', 'Query Credentials'),
            'query_username' => \Yii::t('auth', 'Username'),
            'query_password' => \Yii::t('auth', 'Password'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return array_merge(parent::attributeHints(), [
            'domain' => \Yii::t('auth', 'The full name of the Active Directory Domain. For Exampe <code>test.local</code>.'),
            'ldap_uri' => Yii::t('auth', 'A full LDAP URI of the form <code>ldap://hostname:port</code> or <code>ldaps://hostname:port</code> for SSL encryption. You can also provide multiple LDAP-URIs separated by a space as one string. Note that <code>hostname:port</code> is not a supported LDAP URI as the schema is missing. See <a target="_blank" href="https://www.php.net/manual/en/function.ldap-connect.php">ldap_connect()</a>
                under <code>ldap_uri</code>.'),
            'ldap_options' => Yii::t('auth', 'For all possible options please visit <a target="_blank" href="https://www.php.net/manual/en/function.ldap-set-option.php">ldap_set_option()</a>'),
            'loginScheme' => \Yii::t('auth', 'The pattern to test the given login credentials against a login over AD will only be performed if the given username matches the provided pattern. This is used to manage multiple ADs. {username} is extracted from the username given in the login form. Later in the authentication {username} is replaced by the extracted one from here (see <code>bindScheme</code> and <code>searchFilter</code>).<br>Examples:<br><ul><li><code>{username}</code>: no special testing, all usernames provided are authenticated against the AD.</li><li><code>{username}@foo</code>: only usernames ending with @foo are considered and authenticated agaist the AD.</li><li><code>{username}@{domain}</code>: only usernames ending with @{domain} are considered and authenticated agaist the AD. {domain} is replaced with the given <code>domain</code> configuration variable.</li><li><code>foo\{username}</code>: only usernames starting with foo\ are considered and authenticated agaist the AD.</li></ul>The placeholders that are replaced by the values given are: <code>{domain}</code>, <code>{netbiosDomain}</code>, <code>{base}</code>.'),
            'bindScheme' => \Yii::t('auth', 'TODO'),
            'searchFilter' => \Yii::t('auth', 'TODO'),
            'mapping' => \Yii::t('auth', 'The direct assignment of Active Diretory groups to user roles. You can map multiple Active Diretory groups to the same role. Goups need not to be assigned to all roles.'),
            'query_login' => \Yii::t('auth', 'Username and password to query the Active Diretory servers given above for group names. This information is only needed to specify the group mapping. Login credentials are not saved anywhere.'),
        ]);
    }

    /**
     * Returns a value indicating whether the AD connection is established.
     * @return bool whether the AD connection is established
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
     * Decides whether the username provided by the user matches the pattern to authenticate over AD.
     * @param string username the username that was provided to the login form by the user attempting to login
     *
     * @return bool whether the provided username matches the pattern or not
     */
    public function getRealUsername($username)
    {
        //$regex = '([^\"\/\\\[\]\:\;\|\=\,\+\*\?\<\>]+)';
        $regex = '(.+)';
        $pattern = substitute($this->loginScheme, [
            'domain' => $this->domain,
            'netbiosDomain' => $this->netbiosDomain,
            'base' => $this->base,
            'username' => '@@@@',
        ]);
        $pattern = preg_quote($pattern, '/');
        $pattern = str_replace('@@@@', $regex, $pattern);

        preg_match('/'.$pattern.'/', $username, $matches);

        if (array_key_exists(1, $matches)) {
            return $matches[1];
        }
        return false;
    }

    /**
     * Contructs the username to bind to the AD.
     * @param string username the real username that was extracted from [[getRealUsername()]]
     *
     * @return string the bind username
     */
    public function getBindUsername($username)
    {
        return substitute($this->bindScheme, [
            'domain' => $this->domain,
            'netbiosDomain' => $this->netbiosDomain,
            'base' => $this->base,
            'username' => $username,
        ]);
    }

    /**
     * Retrieves the group names from a list of distinguishedName's
     * @param array dns set of distinguishedName's of groups
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
     * @param array groups set of AD groups
     *
     * @return mixed the highest role according to [[roleOrder]] or the first element from the roles array if nothing matches or false if roles is empty
     */
    public function determineRole($groups)
    {
        $roles = [];
        foreach ($this->mapping as $adGroup => $mappedRole) {
            if (in_array($adGroup, $groups)) {
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
     * Establishes a AD connection.
     * It does nothing if a AD connection has already been established.
     * @throws Exception if connection fails
     */
    public function open()
    {
        if ($this->connection !== null) {
            return;
        }

        if (empty($this->domain)) {
            $this->error = 'Ad::domain cannot be empty.';
            throw new InvalidConfigException('Ad::domain cannot be empty.');
        }

        Yii::debug('AD: Opening AD connection: ' . $this->ldap_uri, __METHOD__);
        $this->debug[] = 'AD: Opening AD connection: ' . $this->ldap_uri;
        $this->connection = ldap_connect($this->ldap_uri);

        if ($this->connection === false) {
            $this->error = 'Ad::ldap_uri was not parseable.';
            throw new InvalidConfigException('Ad::ldap_uri was not parseable.');
        }

        foreach ($this->ldap_options as $option => $value) {
            if(!ldap_set_option($this->connection, $option, $value)) {
                $this->error = 'Unable to set ' . $option . ' to ' . $value . '.';
                throw new NotSupportedException('Unable to set ' . $option . ' to ' . $value . '.');
            } else {
                Yii::debug('AD: Setting ' . $this->ldap_options_name_map[$option] . ' to ' . $value . '.', __METHOD__);
                $this->debug[] = 'AD: Setting ' . $this->ldap_options_name_map[$option] . ' to ' . $value . '.';
            }
        }
    }

    /**
     * Bind to the Active Directory.
     * 
     * @param string $username the username given from the login form
     * @param string $password the password given from the login form
     * @return bool|string bind failure or the username
     */
    public function bindAd($username, $password)
    {
        $this->init();
        if ($user = $this->getRealUsername($username)) {
            $bindUser = $this->getBindUsername($user);
            Yii::debug('AD: Username matches Ad::loginScheme. Proceeding with bind username: ' . $bindUser, __METHOD__);
            $this->debug[] = 'AD: Username matches Ad::loginScheme. Proceeding with bind username: ' . $bindUser;
        } else {
            $this->error = 'AD: Username "' . $username . '" does not match Ad::loginScheme: "' . $this->loginScheme . '"';
            Yii::debug($this->error, __METHOD__);
            return false;
        }

        $this->open();
        $this->bind = @ldap_bind($this->connection, $bindUser, $password);

        if ($this->bind) {
            Yii::debug('AD: Bind successful', __METHOD__);
            $this->debug[] = 'AD: Bind successful';
            return $user;
        } else {
            $this->error = 'AD: Bind failed: ' . ldap_error($this->connection);
            ldap_get_option($this->connection, Ad::LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error);
            if (!empty($extended_error)) {
                $this->error .= ", Detailed error message: " . $extended_error;
            }
            Yii::debug($this->error, __METHOD__);
            $this->close();
            return false;
        }
    }

    /**
     * Authenticate a user over Active Directory.
     * 
     * @param string $username the username given from the login form
     * @param string $password the password given from the login form
     * @return bool authentication success or failure
     */
    public function authenticate($username, $password)
    {

        if (($user = $this->bindAd($username, $password)) !== false) {

            $searchFilter = substitute($this->searchFilter, [
                'domain' => $this->domain,
                'netbiosDomain' => $this->netbiosDomain,
                'base' => $this->base,
                'username' => $user,
            ]);

            $result = ldap_search($this->connection, $this->base, $searchFilter, array($this->uniqueIdentifier, 'memberOf'), 0, 1);

            if ($userInfo = ldap_get_entries($this->connection, $result)) {
                if($userInfo['count'] != 0) {
                    $this->identifier = $this->convertGUIDToHex($userInfo[0][strtolower($this->uniqueIdentifier)][0]);
                    $memberOf = $userInfo[0][strtolower('memberOf')];
                    $groups = $this->getGroupNames($memberOf);

                    if ($this->role = $this->determineRole($groups)) {
                        $this->close();
                        Yii::debug('AD: role=' . $this->role . ', identifier=' . $this->identifier, __METHOD__);
                        Yii::debug('AD: Authentication was successful.', __METHOD__);
                        return true;
                    } else {
                        $this->error = 'AD: No role found, check Ad::roleOrder and Ad:mapping.';
                        Yii::debug($this->error, __METHOD__);
                        $this->close();
                        return false;
                    }
                } else {
                    $this->error = 'AD: No result found, check Ad::searchFilter.';
                    Yii::debug($this->error, __METHOD__);
                    return false;
                }
            } else {
                $this->error = ldap_error($this->connection);
                Yii::debug('AD: recieving entries failed: ' . $this->error, __METHOD__);
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
        $this->debug[] = 'AD: Querying AD with search filter "' . $this->groupSearchFilter . '" and base dn "' . $this->base . '" for the attribute "' . $this->groupIdentifier . '"';
        $result = @ldap_search($this->connection, $this->base, $this->groupSearchFilter, array($this->groupIdentifier), 0, 0);

        if ($result === false) {
            $this->error = 'AD: search failed:' . ldap_error($this->connection);
            Yii::debug($this->error, __METHOD__);
            return false;
        }

        if ($groupInfo = ldap_get_entries($this->connection, $result)) {
            if($groupInfo['count'] != 0) {
                $this->success = 'AD: Retrieving ' . $groupInfo['count'] . ' group entries.';
                $groupName = $groupInfo[0][strtolower($this->groupIdentifier)];
                $groups = array_column($groupInfo, strtolower($this->groupIdentifier));
                $groups = array_column($groups, 0);
                $groups = array_combine($groups, $groups);
                //$this->debug[] = print_r($groups, true);
                //var_dump($groups);
                $this->groups = $groups;
                return true;
            } else {
                $this->error = 'AD: No result found, check Ad::groupSearchFilter.';
                Yii::debug($this->error, __METHOD__);
                return false;
            }
        } else {
            $this->error = 'AD: recieving group entries failed: ' . ldap_error($this->connection);
            Yii::debug($this->error, __METHOD__);
            return false;
        }
    }

    /**
     * Closes the currently active AD connection.
     * It does nothing if the connection is already closed.
     */
    public function close()
    {
        if ($this->connection !== null) {
            Yii::debug('AD: Closing AD connection: ' . $this->domain, __METHOD__);
            @ldap_close($this->connection);
        }
    }
}
