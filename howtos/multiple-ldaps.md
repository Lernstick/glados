## Multiple LDAP Servers and/or Active Directories

This article covers the authentication of users over multiple LDAP domains and/or Active Directory domains. You have the possibility to provide as many authentication methods as you want. You can also combine differnt LDAP servers with different domains.

Under `System->Authentication Methods` you see a list of all defined authentication methods. One method is always available: the local database. This entry is whether editable nor deletable and the `Login Scheme` is fixed. To deal with multiple authentication methods, you have to specify an `Order` in which they have to be processed during a login. The first item with `Order` 0 is the local user database maintained by GLaDOS itself.

> All login attempts will first be authenticated through this local method. This cannot be changed.

If the authentication fails via local database in any way (username does not exist / password is wrong), the authentication method list will be processed further in the given order. When creating a new authentication method it will automatically be appended to the end of the list. The order can be changed by editing the `Order` setting of each involved authentication method.

When dealing with multiple authentication methods, you are encouraged to use different `Login Schemes` for each method. Please read [Login Scheme](login-scheme.md) before you handling multiple authentication methods.

Let's look at an example. Assume you have two Active Directory domains `domain1.local` and `domain2.local`. There may be usernames that appear in both ADs. You can set up the two authentication methods as follows:

* The `Login Scheme` for `domain1.local` set to `{username}@domain1.local`.
* The `Login Scheme` for `domain2.local` set to `{username}@domain2.local`.

You users can now login via both domains by login with their traditional usernames appended with `@domain1.local` or `@domain2.local` depending on to which AD they belong to. If a user happens to exist in both domains with exactly the same username, both users can actually log in by just appending their domain to the username.

You can also set up the two authentication methods as follows:

* The `Login Scheme` for `domain1.local` set to `{username}`.
* The `Login Scheme` for `domain2.local` set to `{username}@domain2.local`.

In this example all users from the AD whose domain name is `domain1.local`, can log in via their traditional usernames, without and appendix. Users of the domain `domain2.local` although must append the domain to their usernames.

You can also set up the two authentication methods as follows:

* The `Login Scheme` for `domain1.local` set to `{username}`.
* The `Login Scheme` for `domain2.local` set to `{username}`.

In this example, all users can login with their traditional usernames, without appendix. But notice that if a user happens to exist in both domains with exactly the same username, it can no longer be distinguished by GLaDOS. If that user is logging in, the authentication flow is as follows:

1. Local login attempt
2. If it fails, login attempt on `domain1.local`
3. If it fails, login attempt on `domain2.local`
4. If it fails, the login has failed.

As you can see, with the `Login Scheme` configuration option you can route the login flow, when dealing with multiple authentication methods.