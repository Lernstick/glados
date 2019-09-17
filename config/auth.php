<?php

/**
 * Please to not edit this file.
 * This file was automatically generated using the web interface.
 */
return [
  'class' => 'app\models\Auth',
  'methods' => 
    array (
      1 => 
      array (
        'class' => 'app\\components\\Ad',
        'name' => 'BWZ',
        'description' => 'Active Directory Authentication Method',
        'domain' => 'bwzofingen.local',
        'ldap_uri' => 'ldap://bwzofingen.local:389',
        'loginScheme' => '{username}@bwz',
        'bindScheme' => '{username}@{domain}',
        'searchFilter' => '(sAMAccountName={username})',
        'groupIdentifier' => 'sAMAccountName',
        'groupSearchFilter' => '(objectCategory=group)',
        'mapping' => 
        array (
          'LG-IT-Admins' => 'admin',
          'Ticket-Agent' => 'teacher',
          'LG-Lehrer-BFS' => 'teacher',
          'Administratoren' => 'teacher',
        ),
      ),
      2 => 
      array (
        'class' => 'app\\components\\Ad',
        'name' => 'LDAP1',
        'description' => 'Test Active Directory connection 1',
        'domain' => 'test.local',
        'ldap_uri' => 'ldap://192.168.0.67:389',
        'loginScheme' => '{username}',
        'bindScheme' => '{username}@{domain}',
        'searchFilter' => '(sAMAccountName={username})',
        'groupIdentifier' => 'sAMAccountName',
        'groupSearchFilter' => '(objectCategory=group)',
        'mapping' => 
        array (
          'admins' => 'admin',
          'teachers' => 'teacher',
        ),
      ),
      3 => 
      array (
        'class' => 'app\\components\\Ad',
        'name' => 'LDAP2',
        'description' => 'Test Active Directory connection 2',
        'domain' => 'test.local',
        'ldap_uri' => 'ldap://192.168.0.67:389',
        'loginScheme' => '{username}@{domain}',
        'bindScheme' => '{username}@{domain}',
        'searchFilter' => '(sAMAccountName={username})',
        'groupIdentifier' => 'sAMAccountName',
        'groupSearchFilter' => '(objectCategory=group)',
        'mapping' => 
        array (
          'admins' => 'admin',
        ),
      ),
      4 => 
      array (
        'class' => 'app\\components\\Ad',
        'name' => 'ADtest',
        'description' => 'Active Directory Authentication Method',
        'domain' => 'asdasd',
        'ldap_uri' => '',
        'loginScheme' => '{username}',
        'bindScheme' => '{username}@{domain}',
        'searchFilter' => '(sAMAccountName={username})',
        'groupIdentifier' => 'sAMAccountName',
        'groupSearchFilter' => '(objectCategory=group)',
        'mapping' => 
        array (
        ),
      ),
      5 => 
      array (
        'class' => 'app\\components\\Ad',
        'name' => 'OpenLDAP',
        'description' => 'Local LDAP',
        'domain' => 'example.com',
        'ldap_uri' => 'ldap://localhost:389',
        'loginScheme' => 'cn={username},{base}',
        'bindScheme' => 'cn={username},{base}',
        'searchFilter' => '(cn={username})',
        'groupIdentifier' => 'cn',
        'groupSearchFilter' => '(objectClass=posixGroup)',
        'mapping' => 
        array (
          'group2' => 'admin',
          'group' => 'teacher',
        ),
      ),
    )
];