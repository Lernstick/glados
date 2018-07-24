## Installation Guide (Debian package)

This guide describes how to install GLaDOS from the Debian package.

-----

### Requirements

To install the needed requirements, run the following command:

    apt-get install apache2 mysql-server php5 php5-mysql php5-gd squashfs-tools rdiff-backup avahi-daemon openssh-client dbconfig-common

### Installation

Get the newest packages from the Github [releases page](https://github.com/imedias/glados/releases).

    curl -L -O https://github.com/imedias/glados/releases/download/1.0.3/glados_1.0.3-1_all.deb
    curl -L -O https://github.com/imedias/glados/releases/download/1.0.3/yii2-glados_2.0.13.1-1_all.deb
    curl -L -O https://github.com/imedias/glados/releases/download/1.0.3/php5-inotify_0.1.6-1_amd64.deb

Install the packages:

    dpkg -i php5-inotify_0.1.6-1_amd64.deb
    dpkg -i yii2-glados_2.0.13.1-1_all.deb
    dpkg -i glados_1.0.3-1_all.deb

You can now access the webinterface by the URL [http://localhost/glados](http://localhost/glados).

You may login with **admin/admin** or **teacher/teacher**.
To modify the users, please login as **admin**.
