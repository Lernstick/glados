<?php

/**
 * Please to not edit this file.
 * This file was automatically generated using the web interface.
 */
return [
  'class' => 'app\models\Auth',
  'methods' => 
    array (
      'a8cb944a-f35b-49e2-8a4f-2d81c3ab18f0' => 
      array (
        'class' => 'app\\components\\Ad',
        'name' => 'BWZ',
        'description' => 'Active Directory Authentication Method',
        'order' => '3',
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
      '608254f4-a6f9-45e9-967a-0f453835bed4' => 
      array (
        'class' => 'app\\components\\Ad',
        'name' => 'LDAP1',
        'description' => 'Test Active Directory connection 1',
        'order' => '2',
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
      '396bdbe5-224f-44a8-aa60-6104c4bfdbeb' => 
      array (
        'class' => 'app\\components\\Ad',
        'name' => 'LDAP2',
        'description' => 'Test Active Directory connection 2',
        'order' => '1',
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
      'b414b6db-4eb5-41a2-9fcc-97655e56329e' => 
      array (
        'class' => 'app\\components\\Ad',
        'name' => 'OpenLDAP',
        'description' => 'Local LDAP',
        'order' => '4',
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
      '35e7f28a-8fb4-4aa5-b505-8c894be87f1f' => 
      array (
        'class' => 'app\\components\\Ad',
        'name' => 'AD',
        'description' => 'Active Directory Authentication Method',
        'order' => '5',
        'domain' => 'sss',
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
    )
];