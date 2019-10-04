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
        'class' => 'app\\components\\AuthAdExtended',
        'name' => 'BWZ',
        'method' => 'bind_by_binduser',
        'bindUsername' => 'a@asdasd',
        'bindPassword' => '****',
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
        'class' => 'app\\components\\AuthAd',
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
        'class' => 'app\\components\\AuthAd',
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
        'class' => 'app\\components\\AuthGenericLdap',
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
      'c1e3a05e-ca01-4051-96fb-ee824fc2b560' => 
      array (
        'class' => 'app\\components\\AuthGenericLdap',
        'name' => 'LDAP',
        'description' => 'Generic LDAP Authentication Method',
        'order' => '5',
        'domain' => 'test',
        'ldap_uri' => '',
        'loginScheme' => '{username}',
        'bindScheme' => '{username}@{domain}',
        'searchFilter' => '(uid={username})',
        'groupIdentifier' => 'cn',
        'groupSearchFilter' => '(objectClass=posixGroup)',
        'mapping' => 
        array (
        ),
      ),
    )
];