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
}
