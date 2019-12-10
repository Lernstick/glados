## Login Scheme

Every authentication method has a field called `Login Scheme`. This is a pattern to test the given login username against. A login via the associated authentication method will only be performed, if the given username matches the provided pattern. This is used to manage multiple authentication servers and methods, for example multiple LDAP or Active Directory servers with different domains, but can also be used in an environment with one single LDAP server.

`{username}` is extracted from the username provided in the login form according to the `Login Scheme`.

Examples:

* `{username}`: no special testing, all usernames provided are considered for authentication.
* `{username}@foo`: only usernames ending with `@foo` are considered.
* `foo\{username}`: only usernames starting with `foo\` are considered.
* `{username}@{domain}`: only usernames ending with `@{domain}` are considered.

The follwing table gives some more detailed examples on how the `Login Scheme` affects user authentication:

Login Scheme			| Example Login 		| Authenticated?| Extracted Username 	| Notes				|
-------------			| ---------------		| ------------  | ------------------ 	|					|
`{username}`			| `alice`				| yes			| `alice`				| no excluded usernames	|
`{username}`			| `bob`					| yes			| `bob`					| no excluded usernames |
`{username}`			| `alice@example.com`	| yes			| `alice@example.com`	| no excluded usernames |
`{username}@foo`		| `alice`				| no			| none					| only usernames ending with `@foo` |
`{username}@foo`		| `alice@foo`			| yes			| `alice`				| only usernames ending with `@foo` |
`{username}@foo`		| `bob@foo`				| yes			| `bob`					| only usernames ending with `@foo` |
`foo\{username}`		| `alice`				| no			| none					| only usernames starting with `foo\` |
`foo\{username}`		| `foo\alice`			| yes			| `alice`				| only usernames starting with `foo\` |
`{username}@{domain}`	| `alice`				| no 			| none					| where `Domain` is `example.com` |
`{username}@{domain}`	| `alice@example.com`   | yes 			| `alice`				| where `Domain` is `example.com` |
`{username}@{domain}`	| `alice@example.com`   | yes 			| `alice`				| where `Domain` is `example.com` |

----

In the above examples, `{domain}` is a placeholder for the configuration setting `Domain`. You can also use other placeholders. For a full list, please refer to [Placeholders](auth-placeholders.md).