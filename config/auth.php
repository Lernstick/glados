<?php

return [
	0 => [
		'class' => 'app\components\Ad',
		// An array of your AD hosts. You can use either
		// the host name or the IP address of your host.
		'name' => 'BWZ',
		'domain' => 'bwzofingen.local',
		'mapping' => [
			'LG-IT-Admins' => 'admin',
			'Ticket-Agent' => 'teacher',
			'LG-Lehrer-BFS' => 'teacher',
			'Administratoren' => 'teacher',
		],
		'loginScheme' => '{username}',
		'bindScheme' => '{username}@{domain}',
		'searchFilter' => '(sAMAccountName={username})',
	],
	1 => [
		'class' => 'app\components\Ad',
		// An array of your AD hosts. You can use either
		// the host name or the IP address of your host.
		'name' => 'KSZ',
		'domain' => 'kszofingen.local',
		'ldap_uri' => 'ldap://172.10.0.4:389 ldap://172.10.0.15:389',
		'mapping' => [
			'Administratoren' => 'admin',
			'Teacher' => 'teacher',
		],
		'loginScheme' => '{username}',
		'bindScheme' => '{username}@{domain}',
		'searchFilter' => '(sAMAccountName={username})',
	],
];