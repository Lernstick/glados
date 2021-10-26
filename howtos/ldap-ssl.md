## LDAP with SSL

If the Glados server does not trust the certificate chain of a secure LDAP server the [test login](test-login.md) form issues an error `Can't contact LDAP server` or similar.

In order to connect to an LDAP server via SSL, the system running Glados has to trust the secure LDAP server certificate. You may have to include the CA certificate of your LDAP/AD server. First you need to export the CA certificate of your LDAP server in the `crt` format.

On Debian you can do the following. Copy the CA certificate `Domain-CA.crt` to `/usr/local/share/ca-certificates/`

Then run the program that updates the certificate store of your server:

    update-ca-certificates -v

To test whether the certificate is trusted, use:

    echo | openssl s_client -showcerts -connect hostname.domain.local:636

where `hostname.domain.local` denotes the FQDN of the LDAP server and `636` is the LDAPS port number. You should observe an output like the following if the certificate was trusted:

    CONNECTED(00000003)
    depth=1 DC = local, DC = domain, CN = Domain-CA
    verify return:1
    depth=0
    verify return:1
    ---
    Certificate chain
     0 s:
       i:DC = local, DC = domain, CN = Domain-CA
    -----BEGIN CERTIFICATE-----
    [...]
    -----END CERTIFICATE-----
    ---
    Server certificate
    subject=

    issuer=DC = local, DC = domain, CN = Domain-CA

    ---
    No client certificate CA names sent
    Client Certificate Types: RSA sign, DSA sign, ECDSA sign
    Requested Signature Algorithms: RSA+SHA256:RSA+SHA384:RSA+SHA1:ECDSA+SHA256:ECDSA+SHA384:ECDSA+SHA1:DSA+SHA1:RSA+SHA512:ECDSA+SHA512
    Shared Requested Signature Algorithms: RSA+SHA256:RSA+SHA384:ECDSA+SHA256:ECDSA+SHA384:RSA+SHA512:ECDSA+SHA512
    Peer signing digest: SHA256
    Peer signature type: RSA
    Server Temp Key: ECDH, P-384, 384 bits
    ---
    SSL handshake has read 2057 bytes and written 487 bytes
    Verification: OK
    ---
    New, TLSv1.2, Cipher is ECDHE-RSA-AES256-GCM-SHA384
    Server public key is 2048 bit
    Secure Renegotiation IS supported
    Compression: NONE
    Expansion: NONE
    No ALPN negotiated
    SSL-Session:
        Protocol  : TLSv1.2
        Cipher    : ECDHE-RSA-AES256-GCM-SHA384
        Session-ID: 4525000086F0C2A132B4B44AF39E8AE64AD18F8D1B5ABC64B575B19B50E93045
        Session-ID-ctx:
        Master-Key: 31E243F284265AE6AC15C9BFF8FAFA8FB5F3F9E0BE8E6989B0D1BC5EFA165DE4BC0F89A61E6416BA1BAE4F866C6018F5
        PSK identity: None
        PSK identity hint: None
        SRP username: None
        Start Time: 1632386882
        Timeout   : 7200 (sec)
        Verify return code: 0 (ok)
        Extended master secret: yes
    ---
    DONE


If the certificate was not trusted instead it may look like this:

    CONNECTED(00000003)
    depth=0 CN = hostname.domain.local
    verify error:num=20:unable to get local issuer certificate
    verify return:1
    [...]
    SSL handshake has read 2115 bytes and written 487 bytes
    Verification error: unable to verify the first certificate
    [...]
    SSL-Session:
    [...]
        Protocol  : TLSv1.2
        Cipher    : ECDHE-RSA-AES256-GCM-SHA384
    [...]
        Verify return code: 21 (unable to verify the first certificate)
    [...]


As soon as the verification using the `openssl` command is successful you can [authenticate via LDAP](ldap-authentication.md) using SSL. For this, choose `ldaps` as `Connection Method` and `636` as `LDAP Port` or prefix your `LDAP URI` with `ldaps://`.
