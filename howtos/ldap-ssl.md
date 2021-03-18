## LDAP with SSL

If the Glados server does not trust the certificate chain of a secure LDAP server the [test login](test-login.md) form issues a error `Can't contact LDAP server`.

In order to connect to a LDAP server via SSL, the system running Glados has to trust the secure LDAP server certificate. You may have to include the CA certificate of your LDAP/AD server. First you need to export the CA certificate of your LDAP server in the `crt` format.

On Debian you can do the following. Copy the CA certificate `hostname.crt` to `/usr/local/share/ca-certificates/`

Then run the program that updates the certificate store of your server:

	update-ca-certificates -v

To test whether the certificate is trusted, use:

	openssl s_client -showcerts -connect hostname:636

where `hostname` denotes the FQDN of the LDAP server (that occures equally in the certificate file) and `636` is the LDAPS port number. You should observe an output like the following if the certificate was trusted:

	CONNECTED(00000003)
	[...]
	SSL handshake has read 2109 bytes and written 346 bytes
	Verification: OK
	[...]

If the certificate was not trusted instead it may look like this:

	CONNECTED(00000003)
	[...]
	SSL handshake has read 2109 bytes and written 346 bytes
	Verification error: unable to verify the first certificate
	[...]

As soon as the verification using the `openssl` command is successful you can [authenticate via LDAP](ldap-authentication.md) using SSL. For this choose `ldaps` as `Connection Method` and `636` as `LDAP Port` or prefix your `LDAP URI` with `ldaps://`.
