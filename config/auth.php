<?php

/**
 * Please to not edit this file.
 * This file was automatically generated using the web interface.
 */
return [
  'class' => 'app\models\Auth',
  'methods' => 
    array (
      'f9649c00-034d-4346-9c71-452b5cd37ce9' => 
      array (
        'class' => 'app\\components\\AuthActiveDirectory',
        'name' => 'AD',
        'description' => 'Active Directory Authentication Method',
        'order' => '1',
        'domain' => 'test.local',
        'connection_method' => 'connect_via_uri',
        'ldap_uri' => 'ldap://192.168.0.52:389',
        'ldap_scheme' => 'ldap',
        'ldap_port' => '389',
        'loginScheme' => '{username}',
        'groupIdentifier' => 'sAMAccountName',
        'groupSearchFilter' => '(objectCategory=group)',
        'mapping' => 
        array (
          'admin' => 'admin',
          'example' => 'example',
          'teacher' => 'teacher',
        ),
        'uniqueIdentifier' => 'objectGUID',
        'groupMemberAttribute' => 'member',
        'groupMemberUserAttribute' => 'distinguishedName',
        'userSearchFilter' => '(objectCategory=person)',
        'primaryGroupUserAttribute' => 'primaryGroupID',
        'primaryGroupGroupAttribute' => 'objectSid',
        'method' => 'bind_direct',
        'bindScheme' => '{username}@{domain}',
        'loginSearchFilter' => '(& {userSearchFilter} (sAMAccountName={username}) )',
        'bindAttribute' => 'userPrincipalName',
      ),
    )
];