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
    public $identifier;
    public $role = 'teacher';
    public $base = null;

    /**
     * @var array key value pairs of mappings of AD groups (by sAMAccountName) to roles
     */
    public $namedMapping = [];

    /**
     * @var array key value pairs of mappings of AD groups (by objectGUID) to roles
     */
    public $ldapMapping = [];

    /**
     * @var int 0 or 1.
     * * 0 means the bind user is "username@domain"
     * * 1 means the bind user is "netbiosDomain\username"
     */
    public $bindUserSyntax = 0;

    /**
     * @var string A unique identifier across the Active Directory (that never changes)
     * "The GUID is unique across the enterprise and anywhere else."
     * @see https://blogs.msdn.microsoft.com/openspecification/2009/07/10/understanding-unique-attributes-in-active-directory/
     */
    public $uniqueIdentifier = 'objectGUID';


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
     * Establishes a AD connection.
     * It does nothing if a AD connection has already been established.
     * @throws Exception if connection fails
     */
    public function open()
    {
        if ($this->connection !== null) {
            return;
        }

        if (empty($this->ldap_uri)) {
            throw new InvalidConfigException('Connection::ldap_uri cannot be empty.');
        }

        Yii::info('Opening AD connection: ' . $this->domain, __METHOD__);
        $this->connection = ldap_connect($this->ldap_uri);

        if ($this->connection === false) {
            throw new InvalidConfigException('Connection::ldap_uri was not parseable.');
        }

        foreach ($this->ldap_options as $option => $newval) {
            if(!ldap_set_option($this->connection, $option, $newval)) {
                throw new NotSupportedException('Unable to set ' . $option . ' to ' . $newval . '.');
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
        $this->open();
        $bindUser = $this->bindUserSyntax == 0
            ? $username . "@" . $this->domain
            : $this->netbiosDomain . "\\" .  $username;

        $this->bind = @ldap_bind($this->connection, $bindUser, $password);

        if ($this->bind) {
            $filter = "(sAMAccountName=$username)";
            $result = ldap_search($this->connection, $this->base, $filter, array($this->uniqueIdentifier, 'memberOf'), 0, 1);
            $info = ldap_get_entries($this->connection, $result);
            for ($i=0; $i<$info["count"]; $i++)
            {
                if($info['count'] > 1) {
                    break;
                }
                $this->identifier = $this->convertGUIDToHex($info[$i][strtolower($this->uniqueIdentifier)][0]);
            }
            $this->close();
            return true;
        } else {
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
