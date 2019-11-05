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
     * @inheritdoc
     */
    public function getPrimaryGroup($username)
    {

        $searchFilter = $this->getSubstitutedSearchFilter($username);

        $this->debug[] = Yii::t('auth', 'Querying LDAP for primary group with search filter <code>{searchFilter}</code> and base dn <code>{base}</code> for the attributes <code>{attribute1}</code> and <code>{attribute2}</code>.', [
            'searchFilter' => $searchFilter,
            'base' => $this->baseDn,
            'attribute1' => 'objectSid',
            'attribute2' => 'primaryGroupID',
        ]);

        $attributes = array('objectSid', 'primaryGroupID');
        $ret = $this->askFor($attributes, $searchFilter, [
            'limit' => 1,
            'checkAttribute' => 'searchFilter',
        ]);

        if ($ret !== false) {

            $userObjectSid = $ret['objectSid'];
            $primaryGroupID = $ret['primaryGroupID'];
            $userObjectSid = $this->decodeObjectSid($userObjectSid);
            $groupObjectSid = $this->getSidByUserSid($userObjectSid, $primaryGroupID);
            $primaryGroup = $this->getPrimaryGroupBySid($groupObjectSid);
            return $primaryGroup;
            array_unshift($groups, $primaryGroup);
            return $groups;

            return true;
        } else {
            return null;
        }

    }

    /**
     * Trabslates an objectSid from AD to a string
     * @param string $sid the group pbjectSid
     * @return string the group identifier attribute
     */
    public function getPrimaryGroupBySid($sid)
    {

        $searchFilter = substitute('(objectSid={sid})', [
            'sid' => $sid,
        ]);

        $this->debug[] = Yii::t('auth', 'Querying LDAP for primary group with search filter <code>{searchFilter}</code> and base dn <code>{base}</code> for the attribute <code>{attribute}</code>.', [
            'searchFilter' => $searchFilter,
            'base' => $this->baseDn,
            'attribute' => $this->groupIdentifier,
        ]);

        $result = @ldap_search($this->connection, $this->baseDn, $searchFilter, array($this->groupIdentifier), 0, 1);

        if ($result === false) {
            $this->error = 'Search failed: ' . ldap_error($this->connection);
            Yii::debug($this->error, __METHOD__);
            return false;
        }

        if ($info = ldap_get_entries($this->connection, $result)) {
            if($info['count'] != 0) {
                $this->debug[] = Yii::t('auth', 'Retrieving {n} group entries.', [
                    'n' => $info['count'],
                ]);

                $group = $this->get_ldap_attribute($info, $this->groupIdentifier);
                return $group;

            } else {
                $this->error = Yii::t('auth', 'Attribute <code>{attribute}</code> not existing, check <code>groupIdentifier</code>.', [
                    'attribute' =>$this->groupIdentifier,
                ]);
                Yii::debug($this->error, __METHOD__);
                return false;
            }
        } else {
            $this->error = ldap_error($this->connection);
            Yii::debug('Recieving entries failed: ' . $this->error, __METHOD__);
            return false;
        }

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

}
