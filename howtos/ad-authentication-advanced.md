## Active Directory Authentication (Advanced)

This article covers the authentication of users over Active Directory. The advanced setup is very similar to the general LDAP setup, so please first read [LDAP Authentication](ldap-authentication.md).

### Step 1: Choose an authentication type

When creating a new authentication method, choose `Microsoft Active Directory` from the dropdown list. In doing so, all special settings are adjusted to Active Directory LDAP.

### Step 2: Setup

#### Domain / LDAP URI

> Please refer to [LDAP Authentication](ldap-authentication.md) for more information about these fields.

#### Login Scheme

> For more information about the `Login Scheme` field, please read the article [Login Scheme](login-scheme.md) first.

#### Group Mapping

> Please refer to [LDAP Authentication](ldap-authentication.md) for more information about these fields.

#### Expert Settings

##### **Choosing a bind method**

As bind `Method` you can choose between `bind directly by the login credentials` and `bind by given username and password`. As for the `bind with anonymous user` method, you have to explicitly allow this on any Active Directory server. So this is not recommended.

###### *Method 1: Bind directly by the login credentials*

Active Directory LDAP servers allow the user to bind with both the `destinguishedName` or the `userPrincipalName` attribute. Usually the `userPrincipalName` is the username (`sAMAccountName`) glued with an `@` to the AD domain name.

Assume your AD domain name is `example.com`. Below are a few examples how to set up the authentication method for different outcomes:

Login Scheme            | Bind Scheme           | Example Login         | Constructed Bind DN | Authenticated?  | Notes         |
-------------           | ---------------       | ------------          | -----------------   | --------------- | ------------- |
`{username}`            | `{username}@{domain}` | `alice`               | `alice@example.com` | yes             | default setup |
`{username}`            | `{username}`          | `alice`               | `alice`             | no              | |
`{username}`            | `{username}@{domain}` | `alice@example.com`   | `alice@example.com@example.com` | no  | |
`{username}@{domain}`   | `{username}@{domain}` | `alice`               | none                | no              | |
`{username}@{domain}`   | `{username}@{domain}` | `alice@example.com`   | `alice@example.com` | yes             | |
`{username}@{domain}`   | `{username}@{domain}` | `alice@other_domain`  | none                | no              | |
`{username}@other_doman`| `{username}@{domain}` | `alice@other_domain`  | `alice@example.com` | yes             | rewriting of the domain |
`{username}@other_doman`| `{username}@{domain}` | `alice@example.com`   | none                | no              | |

###### *Method 2: Bind anonymously*

> This method will not work unless you have explicitly allowed this in you AD configuration.

###### *Method 3: Bind by given username and password*

For this you have to provide credentials of an account that has the permission to browse the AD directory. The advantage of this method is that only that specific user needs permissions to browse the LDAP directory - the login user itself does not need any permissions.

A common setup could look like this:

```php
loginAttribute = 'mail';
bindAttribute = 'userPrincipalName';
```

With that configuration, the user is able to login with his/her E-Mail address deposited in the AD directory (this is not default) in the `mail` attribute.

> For Active Directory the `Bind Attribute` can only be either `userPrincipalName` or `distinguishedName`.
