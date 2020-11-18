## Softwareupdate on Debian

This guide describes how to update GLaDOS on a Debian server using the deb-packages.

> If you plan to update GLaDOS version `<=1.0.4` on a Debian 8 server, please notice that GLaDOS version `1.0.5` and onwards will **not support Debian 8 anymore**. Please consider an upgrade of Debian 8 to Debian 9 following this [Guide](deb-8to9-upgrade.md).

----

### Updating GLaDOS

To update GLaDOS, simply download the desired release version on [GitHub](https://github.com/imedias/glados/releases). Download both `glados_<glados_version>-deb9_all.deb` and `yii2-glados_<yii_version>_all.deb` to your server.

First update the local package information list:

    apt update

Install all available updates:

    apt upgrade

Install the newest version by executing (inside the directory of the downloaded files):

    apt install ./yii2-glados_<yii_version>_all.deb ./glados_<glados_version>-deb9_all.deb

During the update you may be asked some questions:

* *Configuration file '/etc/glados/params.php' ==> Modified (by you or by a script) since installation.*: `Y`

      Configuration file '/etc/glados/params.php'
       ==> Modified (by you or by a script) since installation.
       ==> Package distributor has shipped an updated version.
         What would you like to do about it ?  Your options are:
          Y or I  : install the package maintainer's version
          N or O  : keep your currently-installed version
            D     : show the differences between the versions
            Z     : start a shell to examine the situation
       The default action is to keep your current version.
      *** params.php (Y/I/N/O/D/Z) [default=N] ? Y

* *Upgrade database for glados with dbconfig-common?*: `yes`

You might have to adjust settings in config files you have done so far (see [Glados config files](config-files.md)). Your existing installation will be updated and all needed dependencies are automatically installed.