<?php

namespace app\components;
 
use Yii;
use yii\base\Component;
use app\models\User;
use yii\base\InvalidConfigException;

/**
 * AuthActiveDirectory represents a connection to a Active Directory via LDAP with extended config options.
 *
 * @inheritdoc
 */
class AuthActiveDirectory extends AuthGenericLdap
{

    public $netbiosDomain = '';

    /**
     * @inheritdoc
     */
    public $class = 'app\components\AuthActiveDirectory';

    /**
     * @inheritdoc
     */
    public $type = \app\models\Auth::AUTH_ACTIVE_DIRECTORY;

    /**
     * @inheritdoc
     */
    public $typeName = 'Active Directory';

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
    public $loginSearchFilter = '(& {userSearchFilter} (sAMAccountName={username}) )';

    /**
     * @inheritdoc
     */
    public $groupSearchFilter = '(objectCategory=group)';

    /**
     * @inheritdoc
     */
    public $userSearchFilter = '(objectCategory=person)';

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
    public $primaryGroupGroupAttribute = 'objectSid';


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
     *
     * Add objectSid and primaryGroupID to the list of substitution attributes.
     */
    public function substitutionProperties()
    {
        return array_merge(parent::substitutionProperties(), [
            'netbiosDomain' => $this->netbiosDomain,
            'primaryGroupUserAttribute' => 'primaryGroupID',
            'primaryGroupGroupAttribute' => 'objectSid',
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
     * Add objectSid and primaryGroupID to the list of user attributes.
     */
    public function getUserAttributes()
    {
        return array_merge(parent::getUserAttributes(), ['objectSid', 'primaryGroupID']);
    }

    /**
     * @inheritdoc
     * 
     * Inject the objectSid=... part into the searchFilter
     */
    public function substitute($string, $params)
    {
        if ($string == $this->groupMembershipSearchFilter) {
            $rid = $this->userObject['primaryGroupID'];
            $userSid = $this->decodeObjectSid($this->userObject['objectSid']);
            $groupSid = $this->getSidByUserSid($userSid, $rid);

            $params = array_merge($params, [
                'primaryGroup' => $groupSid,
            ]);
        }
        return parent::substitute($string, $params);
    }

}
