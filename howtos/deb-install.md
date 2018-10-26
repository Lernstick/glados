## Installation Guide (Debian package)

This guide describes how to install GLaDOS from the Debian package.

-----

### Debian 8

#### Requirements

To install the needed requirements, run the following command:

    apt-get install apache2 mysql-server php5 php5-mysql php5-gd squashfs-tools rdiff-backup avahi-daemon openssh-client dbconfig-common

#### Installation

Get the newest packages from the Github [releases page](https://github.com/imedias/glados/releases).

Install the packages in the following order (for example version `1.0.3`):

    dpkg -i php5-inotify_0.1.6-1_amd64.deb
    dpkg -i yii2-glados_2.0.13.1-1_all.deb
    dpkg -i glados_1.0.3-1_all.deb

You can now access the webinterface by the URL [http://localhost/glados](http://localhost/glados).

You may login with **admin/admin** or **teacher/teacher**.
To modify the users, please login as **admin**.

### Debian 9

Get the newest packages from the Github [releases page](https://github.com/imedias/glados/releases).

Install the packages in the following order (for example version `1.0.3`):

    dpkg -i yii2-glados_2.0.13.1-1_all.deb
    dpkg -i glados_1.0.3-1_all.deb

This will most probably fail due to dependency issues. To install all needed dependencies and to complete the installation execute:

    apt-get -f install

You can now access the webinterface by the URL [http://localhost/glados](http://localhost/glados).

You may login with **admin/admin** or **teacher/teacher**.
To modify the users, please login as **admin**.