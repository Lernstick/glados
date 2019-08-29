<?php

return [
	'class' => 'app\components\Ad',
	// An array of your AD hosts. You can use either
	// the host name or the IP address of your host.
	'domain' => 'test.local',
	'ldap_uri' => 'ldap://192.168.0.67:389',
	'namedMapping' => [
		'admins' => 'admin',
		'teachers' => 'teacher',
	],
];