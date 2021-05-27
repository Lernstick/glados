## Installation Guide (Debian package)

This guide describes how to install GLaDOS from the Debian package.

-----

### Debian 9/10

Download the newest packages from the Github [releases page](https://github.com/imedias/glados/releases).

Download both `glados_<glados_version>-deb9_all.deb` and `yii2-glados_<yii_version>_all.deb` to your server. Simply install the two packages using the following command inside the directory of the downloaded files (substituting the corresponding `<yii_version>` and `<glados_version>` you've just downloaded):

    apt install ./yii2-glados_<yii_version>_all.deb ./glados_<glados_version>-deb9_all.deb

During the installation you will be asked some questions (You should note down these settings):

* *Configure database for glados with dbconfig-common?*: `yes`
* *MySQL password for glados*: `<your_desired_password>`
* *Repeat password*: `<your_desired_password>`

If this fails due to dependency issues, install the needed dependencies and complete the installation by running:

    apt-get -f install

You can now access the webinterface by the URL [http://localhost/glados](http://localhost/glados).

You may login with **admin/admin** or **teacher/teacher**.
To modify the users, please login as **admin**.

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
