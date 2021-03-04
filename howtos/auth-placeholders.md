### Placeholders

Below you will find a list of placeholders that are replaced by the value of the configuration setting or a variable:

Variable						| Configuration Setting 	| Example Value			| Authentication Methods	|
-------------					| ---------------			| ------------  		| ------------  			|
`{username}`					| derived from `Login Attribute` | `alice` 			| Any |
`{domain}`						| `Domain`					| `example.com`  		| Generic LDAP, OpenLDAP, Active Directory |
`{base}`						| derived from `Domain`		| `dc=example,dc=com` 	| Generic LDAP, OpenLDAP, Active Directory |
`{userSearchFilter}`			| `User Search Filter`		| `(objectClass=inetOrgPerson)` | Generic LDAP, OpenLDAP, Active Directory |
`{groupSearchFilter}`			| `Group Search Filter`		| `(objectClass=groupOfNames)` | Generic LDAP, OpenLDAP, Active Directory |
`{uniqueIdentifier}`			| `Unique User Identifier Attribute` | `objectGUID` | Generic LDAP, OpenLDAP, Active Directory |
`{userIdentifier}`				| `User Identifier Attribute` | `sAMAccountName` 	| Generic LDAP, OpenLDAP, Active Directory |
`{groupIdentifier}`				| `Group Identifier Attribute` | `gid` 				| Generic LDAP, OpenLDAP, Active Directory |
`{groupMemberAttribute}`		| `Group Member Attribute`	| `member`				| Generic LDAP, OpenLDAP, Active Directory |
`{groupMemberUserAttribute}`	| `Group Member User Attribute` | `dn`				| Generic LDAP, OpenLDAP, Active Directory |
`{primaryGroupUserAttribute}`	| `Primary Group User Attribute` | `primaryGroupID` | Generic LDAP, OpenLDAP, Active Directory |
`{primaryGroupGroupAttribute}`	| `Primary Group Group Attribute` | `objectSid`		| Generic LDAP, OpenLDAP, Active Directory |
`{loginAttribute}`				| `Login Attribute`			| `mail`				| Generic LDAP, OpenLDAP, Active Directory |
`{bindAttribute}`				| `Bind Attribute`			| `userPrincipalName`	| Generic LDAP, OpenLDAP, Active Directory |
`{netbiosDomain}`				| derived from `Domain`		| `example`				| Active Directory |
