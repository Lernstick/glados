<?php

namespace app\components;
 
use Yii;
use yii\base\Component;
use app\models\User;
use yii\base\InvalidConfigException;

/**
 * AuthAdExtended represents a connection to a Active Directory via LDAP with extended config options.
 *
 * @inheritdoc
 */
class AuthAdExtended extends AuthGenericLdap
{

    public $netbiosDomain = '';

    /**
     * @inheritdoc
     */
    public $class = 'app\components\AuthAdExtended';

    /**
     * @inheritdoc
     */
    public $type = \app\models\Auth::AUTH_ACTIVE_DIRECTORY_EXTENDED;

    /**
     * @inheritdoc
     */
    public $typeName = 'Active Directory (extended)';

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
    public $name = 'AD';

    /**
     * @inheritdoc
     */
    public $description = 'Active Directory Authentication Method';

    /**
     * @inheritdoc
     *
     * "The GUID is unique across the enterprise and anywhere else."
     * @see https://blogs.msdn.microsoft.com/openspecification/2009/07/10/understanding-unique-attributes-in-active-directory/
     */
    public $uniqueIdentifier = 'objectGUID';

    /**
     * @inheritdoc
     *
     * The sAMAccountName may not be unique "across the enterprise and anywhere else", but it is human readable.
     * @see mapping
     */
    public $groupIdentifier = 'sAMAccountName';
    public $userIdentifier = 'sAMAccountName';

    /**
     * @inheritdoc
     */
    public $searchFilter = '(sAMAccountName={username})';

    /**
     * @inheritdoc
     */
    public $groupSearchFilter = '(objectCategory=group)';

    /**
     * @inheritdoc
     */
    public $migrateUserSearchFilter = '(& (objectCategory=person) ({userIdentifier}={username}) )';

    /**
     * @inheritdoc
     */
    public $bindAttribute = 'userPrincipalName';

    /**
     * @inheritdoc
     */
    public $loginAttribute = 'sAMAccountName';

    /**
     * @inheritdoc
     */
    public $groupMemberAttribute = 'member';

    /**
     * @inheritdoc
     */
    public $groupMemberUserAttribute = 'distinguishedName';

    /**
     * @inheritdoc
     */
    public $primaryGroupUserAttribute = 'primaryGroupID';

    /**
     * @inheritdoc to.
     */
    public $primaryGroupGroupAttribute = 'primaryGroupToken';


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->domain !== '') {
            if ($this->netbiosDomain === '') {
                $this->netbiosDomain = substr($this->domain, 0, strrpos($this->domain, '.'));
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
        ]);
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'netbiosDomain' => Yii::t('auth', 'Netbios Domain'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return array_merge(parent::attributeHints(), [
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getRealUsername($username)
    {
        return $this->getRealUsernameByScheme($username, $this->loginScheme, [
            'domain' => $this->domain,
            'netbiosDomain' => $this->netbiosDomain,
            'base' => $this->base,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSubstitutedBindUsername($username)
    {
        return substitute(parent::getSubstitutedBindUsername($username), [
            'netbiosDomain' => $this->netbiosDomain,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSubstitutedSearchFilter($username)
    {
        return substitute(parent::getSubstitutedSearchFilter($username), [
            'netbiosDomain' => $this->netbiosDomain,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSubstitutedMigrateSearchFilter($username)
    {
        return substitute(parent::getSubstitutedMigrateSearchFilter($username), [
            'netbiosDomain' => $this->netbiosDomain,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getSubstitutedMigrateSearchScheme($username)
    {
        return substitute(parent::getSubstitutedMigrateSearchScheme($username), [
            'netbiosDomain' => $this->netbiosDomain,
        ]);
    }

    /**
     * Trabslates an objectSid from AD to a string
     * @param string $userSid the objectSid of the current user
     * @param string $rid the revision id to change
     * @return string
     * @see https://ldapwiki.com/wiki/ObjectSID
     */
    public static function getSidByUserSid($userSid, $rid)
    {
        return substr($userSid, 0, -4) . strval($rid);
    }

    /**
     * Trabslates an objectSid from AD to a string
     * @param string $bin binary objectSid from ldap_search()
     * @return string
     * @see https://www.null-byte.org/development/php-active-directory-ldap-authentication/
     */
    public static function decodeObjectSid($bin)
    {
       $sid = "S-";
       //$ADguid = $info[0]['objectguid'][0];
       $sidinhex = str_split(bin2hex($bin), 2);
       // Byte 0 = Revision Level
       $sid = $sid.hexdec($sidinhex[0])."-";
       // Byte 1-7 = 48 Bit Authority
       $sid = $sid.hexdec($sidinhex[6].$sidinhex[5].$sidinhex[4].$sidinhex[3].$sidinhex[2].$sidinhex[1]);
       // Byte 8 count of sub authorities - Get number of sub-authorities
       $subauths = hexdec($sidinhex[7]);
       //Loop through Sub Authorities
       for($i = 0; $i < $subauths; $i++) {
          $start = 8 + (4 * $i);
          // X amount of 32Bit (4 Byte) Sub Authorities
          $sid = $sid."-".hexdec($sidinhex[$start+3].$sidinhex[$start+2].$sidinhex[$start+1].$sidinhex[$start]);
       }
       return $sid;
    }

    /**
     * @inheritdoc
     * 
     * Add objectSid to the list of user attributes.
     */
    public function getUserAttributes()
    {
        return array_merge(parent::getUserAttributes(), ['objectSid']);
    }

    /**
     * @inheritdoc
     * 
     * Inject the objectSid=... part into the searchFilter
     */
    public function getSubstitutedGroupSearchFilter($username)
    {
        $rid = $this->userObject[$this->primaryGroupUserAttribute];
        $userSid = $this->decodeObjectSid($this->userObject['objectSid']);
        $groupSid = $this->getSidByUserSid($userSid, $rid);

        return substitute($this->groupMembershipSearchFilter, [
            'groupSearchFilter' => $this->groupSearchFilter,
            'groupMemberAttribute' => $this->groupMemberAttribute,
            'groupMemberUserIdentifier' => $this->userObject[$this->groupMemberUserAttribute],
            'primaryGroupUserAttribute' => 'objectSid',
            'primaryGroup' => $groupSid,
            'username' => $username,
        ]);
    }

}
