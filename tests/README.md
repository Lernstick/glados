# Testing

This directory contains various tests for the basic application.

* Create `exam_tests` database and update it by applying migrations (you may skip this step if you do not have created any migrations yet). In the installation directory of glados (usually `/usr/share/glados`) run:

```shell
tests/bin/yii migrate --migrationPath=@yii/rbac/migrations --interactive=0
tests/bin/yii rbac/init --interactive=0
tests/bin/yii migrate --interactive=0
```

Create a symlink to the vendor dir in `/usr/share/glados`:

    ln -s /usr/share/yii2 /usr/share/glados/vendor

----

> In order to be able to run acceptance tests you need to start a webserver.

## Local Tests

Local acceptance tests need

    apt-get install xvfb default-jre

Download and save to `/usr/share/glados/tests/bin/`

* `selenium-server-standalone-3.141.59.jar` (or the latest one for chrome)
* `selenium-server-standalone-3.4.0.jar` (for firefox & ie)

### Chrome

* The chrome browser can be emulated using `chromedriver`

    apt-get install chromedriver

* Or get the latest release of `chromedriver` on https://sites.google.com/a/chromium.org/chromedriver/downloads

```shell
unzip chromedriver_linux64.zip
mv chromedriver /usr/bin/chromedriver
chown root:root /usr/bin/chromedriver
chmod +x /usr/bin/chromedriver
```

Install Google Chrome (TODO):

    wget https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb
    apt install ./google-chrome-stable_current_amd64.deb

### Firefox

The firefox browser can be emulated using `geckodriver`

Install [geckodriver](https://github.com/mozilla/geckodriver/releases) (64-bit)

    tar xfs geckodriver-*.tar.gz

Move the binary to `/usr/share/glados/tests/bin/`

    mv geckodriver /usr/share/glados/tests/bin/

Install Firefox

    apt-get install firefox-esr

## Remote Tests

On the Windows machine download

* `selenium-server-standalone-3.141.59` (or the latest one for chrome)
* `selenium-server-standalone-3.4.0` (for firefox & ie)

Edit `tests/params.php` and set `WINDOWS_HOST` and `GLADOS_HOST`

```php
<?php

return [
    'WINDOWS_HOST' => '<ip of the Windows client>',
    'GLADOS_HOST' => '<ip of the glados server>',
];
```

The file `start.bat` should contain

```bat
START "chrome" java -Dwebdriver.chrome.driver=chromedriver.exe -jar selenium-server-standalone-3.141.59.jar -port 4444
START "firefox" java -Dwebdriver.gecko.driver=geckodriver.exe -jar selenium-server-standalone-3.4.0.jar -port 4445
START "ie" java -Dwebdriver.ie.driver=IEDriverServer.exe -jar selenium-server-standalone-3.4.0.jar -port 4446
```

* [chromedriver.exe](https://sites.google.com/a/chromium.org/chromedriver/downloads)
* [geckodriver.exe](https://github.com/mozilla/geckodriver/releases)
* [IEDriverServer.exe](https://www.seleniumhq.org/download/) (use the 32-bit version, 64-bit is very slow in form inputs)
* [msedgedriver.exe](https://developer.microsoft.com/en-us/microsoft-edge/tools/webdriver/) (TODO)

### Chrome

TODO

### Firefox

TODO

### IE

In a `cmd` of a Windows 7/8/10 client run

    java -Dwebdriver.ie.driver=C:\Path\to\IEDriverServer.exe -jar C:\Path\to\selenium-server-standalone-<version>.jar

* `<version>` being `<=3.4`
* [IEDriverServer.exe](https://www.seleniumhq.org/download/) (use the 32-bit version, 64-bit is very slow in form inputs)

### Edge

In a `cmd` of a Windows 10 client run

    java -Dwebdriver.edge.driver=C:\Path\to\msedgedriver.exe -jar C:\Path\to\selenium-server-standalone-<version>.jar

* [msedgedriver.exe](https://developer.microsoft.com/en-us/microsoft-edge/tools/webdriver/)

## Run the tests

* Now you can run the tests with the following commands:

```shell
# run all available tests
/usr/share/yii2/bin/codecept run
# run acceptance tests
/usr/share/yii2/bin/codecept run acceptance --env <env>
# run functional tests
/usr/share/yii2/bin/codecept run functional
# run unit tests
/usr/share/yii2/bin/codecept run unit
```

where `<env>` can be of value:

* `chrome`: **local** chrome with [chromedriver](https://sites.google.com/a/chromium.org/chromedriver/downloads)
* `ff`: **local** firefox with [geckodriver](https://github.com/mozilla/geckodriver/releases)
* `ff-win`: **remote** firefox on a Windows client
* `chrome-win`: **remote** chrome on a Windows client
* `ie`: **remote** ie on a Windows client
* `edge`: **remote** edge on a Windows client
* `saucelabs`: **cloud** test via [saucelabs](https://saucelabs.com/)
