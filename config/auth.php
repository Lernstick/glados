<?php

/**
 * Please to not edit this file.
 * This file was automatically generated using the web interface.
 */
return [
  'class' => 'app\models\Auth',
  'methods' => 
    array (
      '3df178eb-45b9-479c-b665-a97454706e6b' => 
      array (
        'class' => 'app\\components\\AuthGenericLdap',
        'name' => 'LDAP',
        'description' => 'Generic LDAP Authentication Method',
        'order' => '1',
        'domain' => 'example.com',
        'ldap_uri' => 'ldap://localhost:389',
        'loginScheme' => '{username}',
        'groupIdentifier' => 'cn',
        'groupSearchFilter' => '(objectClass=posixGroup)',
        'mapping' => 
        array (
          'admins' => 'admin',
          'another_group2' => 'teacher',
        ),
        'uniqueIdentifier' => 'uidNumber',
        'groupMemberAttribute' => 'memberUid',
        'groupMemberUserAttribute' => 'uid',
        'userSearchFilter' => '(objectClass=posixAccount)',
        'primaryGroupUserAttribute' => 'gidNumber',
        'primaryGroupGroupAttribute' => 'gidNumber',
        'method' => 'bind_byuser',
        'bindUsername' => 'cn=admin,dc=example,dc=com',
        'bindPassword' => 'test',
        'loginAttribute' => 'uid',
        'bindAttribute' => 'dn',
      ),
      'a2b9bf48-2c60-4d71-b6a4-728857dfd02d' => 
      array (
        'class' => 'app\\components\\AuthActiveDirectory',
        'name' => 'AD_BINDUSE',
        'description' => 'Active Directory Authentication Method',
        'order' => '2',
        'domain' => 'test.local',
        'ldap_uri' => 'ldap://192.168.0.67:389',
        'loginScheme' => '{username}',
        'groupIdentifier' => 'sAMAccountName',
        'groupSearchFilter' => '(objectCategory=group)',
        'mapping' => 
        array (
          'admins' => 'admin',
          'Domänen-Benutzer' => 'teacher',
        ),
        'uniqueIdentifier' => 'objectGUID',
        'groupMemberAttribute' => 'member',
        'groupMemberUserAttribute' => 'distinguishedName',
        'userSearchFilter' => '(objectCategory=person)',
        'primaryGroupUserAttribute' => 'primaryGroupID',
        'primaryGroupGroupAttribute' => 'primaryGroupToken',
        'method' => 'bind_byuser',
        'bindUsername' => 'ad.admin@test.local',
        'bindPassword' => 'admin',
        'loginAttribute' => 'sAMAccountName',
        'bindAttribute' => 'userPrincipalName',
      ),
      'f9ca4350-0769-4929-845a-d05c926a3984' => 
      array (
        'class' => 'app\\components\\AuthActiveDirectory',
        'name' => 'AD_DIRECT',
        'description' => 'Active Directory Authentication Method',
        'order' => '3',
        'domain' => 'test.local',
        'ldap_uri' => 'ldap://192.168.0.67:389',
        'loginScheme' => '{username}',
        'groupIdentifier' => 'sAMAccountName',
        'groupSearchFilter' => '(objectCategory=group)',
        'mapping' => 
        array (
          'admins' => 'admin',
          'test' => 'admin',
          'testäöü' => 'teacher',
        ),
        'uniqueIdentifier' => 'objectGUID',
        'groupMemberAttribute' => 'member',
        'groupMemberUserAttribute' => 'distinguishedName',
        'userSearchFilter' => '(objectCategory=person)',
        'primaryGroupUserAttribute' => 'primaryGroupID',
        'primaryGroupGroupAttribute' => 'primaryGroupToken',
        'method' => 'bind_direct',
        'bindScheme' => '{username}@{domain}',
        'loginSearchFilter' => '(& {userSearchFilter} (sAMAccountName={username}) )',
        'bindAttribute' => 'userPrincipalName',
      ),
    )
];