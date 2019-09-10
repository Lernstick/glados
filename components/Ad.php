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
class Ad extends Component
{

    /**
     * @const int extended error output
     */
    const LDAP_OPT_DIAGNOSTIC_MESSAGE = 0x0032;

    public $ldap_uri = null;
    public $ldap_scheme = 'ldap';
    public $ldap_port = 389;
    public $domain;
    public $netbiosDomain = null;
    public $connection;
    public $bind;
    public $ldap_options = [
        LDAP_OPT_PROTOCOL_VERSION => 3,
        LDAP_OPT_REFERRALS => 0,
    ];
    public $base = null;
    public $error = null;

    /**
     * @var string A name for the current authentication type.
     */
    public $name = 'LDAP';

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
     * @inheritdoc
     */
    public function init()
    {
        if($this->netbiosDomain === null) {
            $this->netbiosDomain = substr($this->domain, 0, strrpos($this->domain, '.'));
        }
        if($this->ldap_uri === null) {
            $this->ldap_uri = $this->ldap_scheme . '://' . $this->domain . ':' . $this->ldap_port;
        }
        if($this->base === null) {
            $this->base = "dc=" . implode(",dc=", explode(".", $this->domain));
        }

        parent::init();
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

        Yii::info('Opening AD connection: ' . $this->domain, __METHOD__);
        $this->connection = ldap_connect($this->ldap_uri);

        if ($this->connection === false) {
            $this->error = 'Ad::ldap_uri was not parseable.';
            throw new InvalidConfigException('Ad::ldap_uri was not parseable.');
        }

        foreach ($this->ldap_options as $option => $value) {
            if(!ldap_set_option($this->connection, $option, $value)) {
                $this->error = 'Unable to set ' . $option . ' to ' . $value . '.';
                throw new NotSupportedException('Unable to set ' . $option . ' to ' . $value . '.');
            }
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

        if ($user = $this->getRealUsername($username)) {
            $bindUser = $this->getBindUsername($user);
        } else {
            $this->error = 'Username does not match Ad::loginScheme.';
            return false;
        }

        $this->open();
        $this->bind = @ldap_bind($this->connection, $bindUser, $password);

        if ($this->bind) {

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
                        return true;
                    } else {
                        $this->error = 'No role found, check Ad::roleOrder and Ad:mapping.';
                        return false;
                    }
                } else {
                    $this->error = 'No result found, check Ad::searchFilter.';
                    return false;
                }
            } else {
                $this->error = ldap_error($this->connection);
                return false;
            }

        } else {
            $this->error = ldap_error($this->connection);
            ldap_get_option($this->connection, Ad::LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error);
            if (!empty($extended_error)) {
                $this->error .= ", Detailed error message: " . $extended_error;
            }
            $this->close();
            return false;
        }
    }

    function convertGUIDToHex($guid)
    {
        $unpacked = unpack('Va/v2b/n2c/Nd', $guid);
        return strtolower(sprintf('%08X-%04X-%04X-%04X-%04X%08X', $unpacked['a'], $unpacked['b1'], $unpacked['b2'], $unpacked['c1'], $unpacked['c2'], $unpacked['d']));
    }

    /**
     * Closes the currently active AD connection.
     * It does nothing if the connection is already closed.
     */
    public function close()
    {
        if ($this->connection !== null) {
            Yii::debug('Closing AD connection: ' . $this->domain, __METHOD__);
            @ldap_close($this->connection);
        }
    }
}
