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
          'teachers' => 'teacher',
        ),
        'uniqueIdentifier' => 'uidNumber',
        'method' => 'bind_byuser',
        'bindUsername' => 'cn=admin,dc=example,dc=com',
        'bindPassword' => 'test',
        'loginAttribute' => 'userid',
        'bindAttribute' => 'dn',
        'userSearchFilter' => '(objectClass=posixAccount)',
      ),
      'a2b9bf48-2c60-4d71-b6a4-728857dfd02d' => 
      array (
        'class' => 'app\\components\\AuthAdExtended',
        'name' => 'AD',
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
          'teachers' => 'teacher',
        ),
        'uniqueIdentifier' => 'objectGUID',
        'method' => 'bind_direct',
        'bindScheme' => '{username}@{domain}',
        'searchFilter' => '(sAMAccountName={username})',
        'bindAttribute' => 'userPrincipalName',
      ),
    )
];