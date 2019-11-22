## LDAP Authentication

This article covers the authentication of users over LDAP. If you plan to use LDAP for authentication on GLaDOS, you have to think about how you want your users to login. LDAP servers provide multiple attributes that are suitable for a login.

### Choosing a bind method

The first thing you have to think about is the way GLaDOS should contact your LDAP server(s). There are 3 methods:

#### Bind directly by the login credentials

If you choose this method, the username provided in the login form is taken directly as bind username to authenticate over LDAP. User details - such as group membership and various needed attributes - are then queried with that login username. Therefore, you can only employ this method if the login user has the permission to browse the LDAP directory tree. This is not the default setting in all LDAP implementations, but very common.

The username that should be used for the bind, can be modified in a simple way. There are 2 configuration options affecting this: `Bind Scheme` and `Login Scheme`.

The `Bind Scheme` is a pattern to build the bind DN for the actual bind to the LDAP server. `{username}` is replaced with the username extracted from `Login Scheme`.

> To understand how the username is extracted, please refer to [Login Scheme](login-scheme.md).

Examples of `Bind Schemes`:

1. `{username}`: no special altering, the extracted username is taken as bind DN as it is.
2. `{username}@foo`: the extracted username is appended with `@foo` to contruct the bind DN.
3. `foo\{username}`: the username is prepended with `foo\` for the bind.
4. `{username}@{domain}`: the username is appended with `@{domain}`, where `{domain}` is replaced with the value given in the configuration.
5. `cn={username},dc=example,dc=com`: a distinguished name is built out of the provided username. Instead of `dc=example,dc=com`, one could have also used `{base}`.

Assume your domain name is `example.com` and your LDAP allows to bind with the distinguished Name `dn` as well as with `username@example.com`. To illustrate the power of `Bind Scheme` together with `Login Scheme` to rewrite bind credentials, consider the following examples:

Login Scheme			| Bind Scheme  			| Example Login			| Constructed Bind DN | Notes 			|
-------------			| ---------------		| ------------  		| -----------------   | ------------- 	|
`{username}`			| `{username}@{domain}`	| `alice`				| `alice@example.com` | default setup 	|
`{username}`			| `{username}`			| `alice`				| `alice`			  | |
`{username}`			| `{username}@{domain}`	| `alice@example.com`	| `alice@example.com@example.com` |  	|
`{username}@{domain}`	| `{username}@{domain}`	| `alice`				| none 				  |  |
`{username}@{domain}`	| `{username}@{domain}`	| `alice@example.com`   | `alice@example.com` |  |
`{username}@{domain}`	| `{username}@{domain}`	| `alice@other_domain`  | none				  |  |
`{username}@other_doman`| `{username}@{domain}`	| `alice@other_domain`  | `alice@example.com` | rewriting of the domain |
`{username}@other_doman`| `{username}@{domain}`	| `alice@other_domain`  | `alice@example.com` |  |
`{username}`			| `cn={username},dc=example,dc=com`	| `alice`   | `cn=alice,dc=example,dc=com` | 		|
`{username}`			| `cn={username},{base}`| `alice`  				| `cn=alice,dc=example,dc=com` 	  		|
`{username}@{domain}`	| `cn={username},{base}`| `alice`			   	| none 				  | 				|
`{username}@{domain}`	| `cn={username},{base}`| `alice@example.com`  	| `cn=alice,dc=example,dc=com` | 		|

#### Bind anonymously

The second method is to bind with an anonymous user. To use this method, your LDAP server must allow anonymous binds. When a user attempts to login, the LDAP directory is browsed with an anonymous bind for the `Bind Attribute` of the login user and his/her group membership. Then GLaDOS performs a second bind, but this time with the value in the users `Bind Attribute` and the provided password. Using this method, you are able to choose the `Login Attribute`, which is the attribute that should be used for the login.

See the following example configuration:

```php
loginAttribute = 'mail';
bindAttribute = 'dn';
```

With that configuration, the user is able to login with his/her E-Mail address deposited in the LDAP directory in the `mail` attribute (this attribute should be unique across the directory). Most LDAP servers only allow to authenticate with the distinguished name `dn`.

> The attribute name of the distinguished name can vary across LDAP implementations. Microsofts Active Directory uses `distinguishedName` instead of `dn`.

#### Bind by given username and password

If your LDAP server does not allow anonymous binds, but you anyhow want to use a special attribute as `Login Attribute`, you can choose this method. For this you have to provide credentials of an account that has the permission to browse the LDAP directory. The advantage of this method is that only that specific user needs permissions to browse the LDAP directory - the login user itself does not need any permissions. However most LDAP implementations do allow every user to browse the LDAP tree. The 2 configuration settings - `Login Attribute` and `Bind Attribute` - behave exactly the same as in the anonymous bind scenario.

### Domain / LDAP URI

The configuration option `Domain` should be set to the full name of your LDAP domain, for example `example.com`. Your server(s) should be 	accessible by this DNS name over the network. If this is the case, you don't have set an `LDAP URI` by hand (and you also should not). The `LDAP URI` is contructed from the LDAP Domain name. Only if your domain name is not resolvable over DNS, you have to provide an `LDAP URI` in the extert settings.

### Group Mapping

You may want to map some groups defined in your LDAP directory to user roles used in GLaDOS. Members of specific groups may become admins and others may become teachers on GLaDOS. When editing or creating an new authentication method, you can specify the group mapping. If you don't know the group names on your LDAP servers, you can read them out in the form by providing a username to query the LDAP. Click `Query for LDAP groups` in the group mapping configuration option. In the appearing window provide login credentials and proceed with `Query`. If the credentials where valid, the dropdown lists will be populated with group names recieved from the LDAP servers. You can choose multiple groups to be mapped to the same role in GLaDOS. You may also want to map no group to some roles.

