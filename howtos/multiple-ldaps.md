## Multiple LDAP Servers and/or Active Directories

This article covers the authentication of users over multiple LDAP domains and/or Active Directory domains. You have the possibility to provide as many authentication methods as you want. You can also combine different LDAP servers with different domains.

Under `System->Authentication Methods` you see a list of all defined authentication methods in their order of processing. One method is always available: the local database. This entry is neither editable nor deletable and the `Login Scheme` is fixed. To deal with multiple authentication methods, you have to specify an `Order` in which they have to be processed during a login. The first item with `Order` 0 is the local user database maintained by GLaDOS itself. The item with `Order` 0 is reserved for the local user database.

> All login attempts will first be authenticated through this local method. This cannot be changed!

If the authentication fails via local database in any way (username does not exist / password is wrong), the authentication method list will be processed further in the given order. When creating a new authentication method it will automatically be appended to the end of the list. The order can be changed by editing the `Order` setting of each involved authentication method.

When dealing with multiple authentication methods, you are encouraged to use different `Login Schemes` for each method. Please read [Login Scheme](login-scheme.md) before you start to handle multiple authentication methods.

### Example 1

Let's have a look at an example. Assume you have two Active Directory domains `domain1.local` and `domain2.local` with `Order` 1 and 2 respectively. There may be usernames that appear in both ADs. You can set up the two authentication methods as follows:

* The `Login Scheme` for `domain1.local` set to `{username}@domain1.local`.
* The `Login Scheme` for `domain2.local` set to `{username}@domain2.local`.

Your users can now login via both domains by login with their traditional usernames appended with `@domain1.local` or `@domain2.local` depending on to which AD they belong to. If a user happens to exist in both domains with exactly the same username, both users can actually log in by just appending their domain to the username. A login of a user existing in `domain1.local` may look like this:

1. User types `user@domain1.local` into the login form
2. Local login attempt (`Order` 0)
3. If it fails, check `Login Scheme` of `domain1.local` (`Order` 1) which matches. Login attempt on `domain1.local` (`Order` 1)
4. If it fails, check `Login Scheme` of `domain2.local` (`Order` 2), but `user@domain1.local` does not match `{username}@domain2.local`
5. Further processing next entry (`Order` 3)

A login of a user existing in `domain2.local`, whould then look like this:

1. User types `user@domain2.local` into the login form
2. Local login attempt (`Order` 0)
3. If it fails, check `Login Scheme` of `domain1.local` (`Order` 1), but `user@domain2.local` does not match `{username}@domain1.local`
4. If it fails, check `Login Scheme` of `domain1.local` (`Order` 1) which matches. Login attempt on `domain2.local` (`Order` 2)
5. Further processing next entry (`Order` 3)

As you can see, the `Login Scheme` can be used to only authentication users matching a scheme and skip some authentication methods based on that scheme.

### Example 2

You can also set up the two authentication methods as follows:

* The `Login Scheme` for `domain1.local` set to `{username}`.
* The `Login Scheme` for `domain2.local` set to `{username}@domain2.local`.

In this example all users from the AD whose domain name is `domain1.local`, can log in via their traditional usernames, without and appendix. Users of the domain `domain2.local` although must append the domain to their usernames.

### Example 3

You can also set up the two authentication methods as follows:

* The `Login Scheme` for `domain1.local` set to `{username}`.
* The `Login Scheme` for `domain2.local` set to `{username}`.

In this example, all users of both domains can login with their traditional usernames, without appendix. But notice that if a user happens to exist in both domains with exactly the same username, it can no longer be distinguished by GLaDOS. If that user is logging in, the authentication flow is as follows:

1. Local login attempt (`Order` 0)
2. If it fails, login attempt on `domain1.local` (`Order` 1), because it matches the `Login Scheme` `{username}`
3. If it fails, login attempt on `domain2.local` (`Order` 2), because it matches the `Login Scheme` `{username}`
4. If it fails, the login has failed.

As you can see, with the `Login Scheme` configuration option you can route the login flow, when dealing with multiple authentication methods.
