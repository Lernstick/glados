## Exam client configuration

To setup an exam client, you can install the package `lernstick-exam-client`:

    apt-get install lernstick-exam-client

The exam client can be configured in 2 ways.

### Autodiscovery of the exam server

To configure the exam client for this behavior, nothing has to be done. After installing the package, you can just search for the exam server, as described in [Taking an Exam](take-exam.md).

> Notice, for this to work, you have to [configure the network](network-config.md) accordingly.

### Exam server with fixed IP-address

If you want a more secure installation and the exam server has a fixed IP-address, you can configure the exam client to only access your fixed exam server.

Create a config file `/etc/lernstick-exam-client.conf` with the following contents:

    gladosIp="1.2.3.4"
    gladosHost="examsrv"
    gladosPort=80
    gladosProto="http"
    gladosDesc="Description"

    actionDownload='glados/index.php?r=ticket/download&token={token}'
    actionFinish='glados/index.php?r=ticket/finish&token={token}'
    actionNotify='glados/index.php?r=ticket/notify&token={token}&state={state}'
    actionSSHKey='glados/index.php?r=ticket/ssh-key'
    actionMd5='glados/index.php?r=ticket/md5&token={token}'
    actionConfig='glados/index.php?r=ticket/config&token={token}'

The below table explaines the keys and values:

Config Item     | Description
------------    | -------------
`gladosIp`      | The IP-address of the exam server.
`gladosHost`    | The DNS name of the exam server.
`gladosPort`    | The port, under which the webserver is running. (Don't use double quotes `"` here)
`gladosProto`   | The protocol to use. Can either be `http` or `https`.
`gladosDesc`    | A short description of the host (for example `Exam Server School A`)
`action*`       | Those variables should mostly be left as in the config above. They describe the URL for the different actions a client can take. Adjust them to the corresponding configuration of your webserver. For example the download URL `actionDownload` will be made up of the hosts IP-address, the port, the protocol and the given relative path. `${gladosProto}://${gladosIp}:${gladosPort}/${actionDownload}` will then be translated to `http://1.2.3.4:80/glados/index.php/ticket/download/{token}` in the example configuration above. That URL must point to the exam server token prompt.

----

If you start the `Search Exam Server` utility now, it will only search for your given IP-address, thus other exam servers in the network will be ignored.