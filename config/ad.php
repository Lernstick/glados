<?php

return [
	'class' => 'app\components\Ad',
	'name' => 'LDAP',
	'domain' => 'bwzofingen.local',
	// An array of your AD hosts. You can use either
	// the host name or the IP address of your host.
	//'ldap_uri' => 'ldap://192.168.0.67:389',
	'mapping' => [
		'LG-IT-Admins' => 'admin',
		'Ticket-Agent' => 'teacher',
		'LG-Lehrer-BFS' => 'teacher',
		'Administratoren' => 'teacher',
	],
	'loginScheme' => '{username}',
	'bindScheme' => '{username}@{domain}',
	'searchFilter' => '(sAMAccountName={username})',
];