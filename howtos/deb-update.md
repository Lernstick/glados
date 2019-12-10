## Softwareupdate on Debian

This guide describes how to update GLaDOS on a Debian server using the deb-packages.

> If you plan to update GLaDOS version `<=1.0.4` on a Debian 8 server, please notice that GLaDOS version `1.0.5` and onwards will **not support Debian 8 anymore**. Please consider an upgrade of Debian 8 to Debian 9 following this [Guide](deb-8to9-upgrade.md).

----

### Updating GLaDOS

To update GLaDOS, simply download the desired release version on [GitHub](https://github.com/imedias/glados/releases). Download both `glados_<glados_version>-deb9_all.deb` and `yii2-glados_<yii_version>_all.deb` to your server.

Install the newest version by executing (inside the directory of the downloaded files)

    apt install ./yii2-glados_<yii_version>_all.deb ./glados_<glados_version>-deb9_all.deb

During the update you will be asked some questions:

* *Upgrade database for glados with dbconfig-common?*: `yes`

Your existing installation will be updated and all needed dependencies are automatically installed.