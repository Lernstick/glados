## User Migration

This article covers the topic on user migration. User migration is needed if you have changed your authentication method A to another authentication method B and you want your existing users to authenticate over the new method. For example, you created 20 users locally on GLaDOS and now you integrated LDAP authentication and you want these 20 users to authenticate over LDAP from now on. In such a case you have to perform a user migration.

Start the user migration form under `System->Authentication Method` and then click `Actions->Migrate User`.

> To be able to migrate users you have to set up an authentication method first, for example [LDAP](ldap-authentication.md) or [Active Directory](ad-authentication-simple.md)

### Step 1: Setup

The first thing you have to choose is from which to which method you want to migrate. In the above example you should choose `Local (Database)` in the `From` dropdown list and your new authentication method in the `To` field. Depending on what you have chosen, the next step will be a bit different.

### Step 2: Query for users

In this step you have to query for users that are able to migrate. You can do so by pressing the `Query for Users` button. You may have to provide credentials for this step. Depending on your migration setup, the dropdown field `Users to migrate` will be filled with users that are able to be migrated.

> Which users are able to migrate depends on your setup.

Essentially, all users associated to the authentication method `From` will be checked whether they could authenticate over the authentication method `To`. In the above example, the local database will be queried for users that authenticate locally. For each found user, the LDAP server is queried whether the user exists in the LDAP diretory or not. So each user existing in both authentication methods (`From` and `To`) will be suggested for migration. The dropdown list is then filled with a list of such users. The query can be modified by the `Migrate Search Pattern` to restrict to usernames matching the pattern.

### Step 3: Select users to migrate

As a last step, you have to explicitly select the users to want to migrate in the dropdown list. Click `Migrate` to migrate all selected users.

### Summary

You will see a summary information about each user migrated of the process after you initiated the user migration. The migrated users can now login over the new authentication method.

> Mirgated users can always be migrated back to their original authentication method.
