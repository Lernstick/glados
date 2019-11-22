## Active Directory Authentication

This article covers the authentication of users over Active Directory. This is a special case of [LDAP Authentication](ldap-authentication.md).

### Simple Setup

When creating a new authentication method, choose `Microsoft Active Directory` from the dropdown list. In doing so, all special settings are adjusted to Active Directory LDAP. If your Active Directory Servers are reachable you can provide the Active Directory Domain Name in the `Domain` field and specify a group mapping to the roles in GLaDOS and you're done!

> When you have not changed any default settting, your users are now able to login to GLaDOS in the same way as they login to a domain joined Windows machine using their `sAMAccountName`.

For the group mapping, you can optionally query for AD groups by pressing `Query for LDAP groups` and providing a username and password. The dropdown lists will then be populated with the query result.

### Advanced Setup

The advanced setup is very similar to the general LDAP setup, so please first read [LDAP Authentication](ldap-authentication.md).

### Choosing a bind method

As bind `Method` you can choose between `bind directly by the login credentials` and `bind by given username and password`. As for the `bind with anonymous user` method, you have to explicitly allow this on any Active Directory server. So this is not recommended.

#### Bind directly by the login credentials

Active Directory LDAP servers allow the user to bind with both the `destinguishedName` or the `userPrincipalName` attribute. Usually the `userPrincipalName` is the username (`sAMAccountName`) glued with an `@` to the AD domain name.

Assume your AD domain name is `example.com`. Below are a few examples how to set up the authentication method for different outcomes:

Login Scheme			| Bind Scheme  			| Example Login			| Constructed Bind DN | Authenticated? 	| Notes 		|
-------------			| ---------------		| ------------  		| -----------------   | --------------- | ------------- |
`{username}`			| `{username}@{domain}`	| `alice`				| `alice@example.com` | yes				| default setup |
`{username}`			| `{username}`			| `alice`				| `alice`			  | no 				| |
`{username}`			| `{username}@{domain}`	| `alice@example.com`	| `alice@example.com@example.com` | no	| |
`{username}@{domain}`	| `{username}@{domain}`	| `alice`				| none 				  | no				| |
`{username}@{domain}`	| `{username}@{domain}`	| `alice@example.com`   | `alice@example.com` | yes				| |
`{username}@{domain}`	| `{username}@{domain}`	| `alice@other_domain`  | none				  | no				| |
`{username}@other_doman`| `{username}@{domain}`	| `alice@other_domain`  | `alice@example.com` | yes 			| rewriting of the domain |
`{username}@other_doman`| `{username}@{domain}`	| `alice@example.com`   | none 				  | no  			| |

#### Bind anonymously

> This method will not work unless you have explicitly allowed this in you AD configuration.

#### Bind by given username and password

For this you have to provide credentials of an account that has the permission to browse the AD directory. The advantage of this method is that only that specific user needs permissions to browse the LDAP directory - the login user itself does not need any permissions.

A common setup could look like this:

```php
loginAttribute = 'mail';
bindAttribute = 'userPrincipalName';
```

With that configuration, the user is able to login with his/her E-Mail address deposited in the AD directory (this is not default) in the `mail` attribute.

> For Active Directory the `Bind Attribute` can only be either `userPrincipalName` or `distinguishedName`.

### Domain / LDAP URI

Please refer to [LDAP Authentication](ldap-authentication.md) for more information.

### Group Mapping

Please refer to [LDAP Authentication](ldap-authentication.md) for more information.
