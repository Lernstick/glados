## Installation Guide (Debian package)

This guide describes how to install Glados from the Debian package.

-----

### Requirements

To install the needed requirements, run the following command:

    apt-get install apache2 mysql-server php5 php5-mysql squashfs-tools rdiff-backup avahi-daemon openssh-client

### Installation

Get the newest packages from the Github [releases page](https://github.com/imedias/glados/releases).

    curl -L -O https://github.com/imedias/glados/archive/glados-1.0.3.deb
    curl -L -O https://github.com/imedias/glados/archive/yii2-glados-2.0.13.1.deb
    curl -L -O https://github.com/imedias/glados/archive/php5-infotify-0.1.6.deb

Install the packages:

    dpkg -i php5-infotify-0.1.6.deb
    dpkg -i yii2-glados-2.0.13.1.deb
    dpkg -i glados-1.0.3.deb

You can now access the webinterface by the URL [http://localhost/glados](http://localhost/glados).