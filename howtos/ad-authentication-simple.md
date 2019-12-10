## Active Directory Authentication (Simple)

This article covers the authentication of users over Active Directory. This is a special case of [LDAP Authentication](ldap-authentication.md).

### Step 1: Choose an authentication type

When creating a new authentication method, choose `Microsoft Active Directory` from the dropdown list. In doing so, all special settings are adjusted to Active Directory LDAP.

### Step 2: Setup

If your Active Directory servers are reachable by the GLaDOS server and your Active Directory Domain Name can be looked up via DNS, you can provide the Active Directory Domain Name in the `Domain` field and specify a group mapping and you're done!

> When you have not changed any default settting, your users are now able to login to GLaDOS in the same way as they are able to login to a domain joined Windows machine using their `sAMAccountName`.

For the group mapping, you can optionally query for AD groups by pressing `Query for LDAP groups` and providing a username and password. The dropdown lists will then be populated with the query result.

### Troubleshooting

> Please refer to the [advanced setup](ad-authentication-advanced.md) if you have trouble configuring Active Directory.