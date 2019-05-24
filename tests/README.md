# Testing

This directory contains various tests for the basic application.

* Create `exam_tests` database and update it by applying migrations (you may skip this step if you do not have created any migrations yet). In the installation directory of glados (usually `/usr/share/glados`) run:

```shell
tests/bin/yii migrate
```

 * In order to be able to run acceptance tests you need to start a webserver.

## Chrome

* The chrome browser can be emulated using `chromedriver`

* Get the latest release of `chromedriver` on https://sites.google.com/a/chromium.org/chromedriver/downloads

```shell
unzip chromedriver_linux64.zip
mv chromedriver /usr/bin/chromedriver
chown root:root /usr/bin/chromedriver
chmod +x /usr/bin/chromedriver
```

* Start a chrome webdriver with

```shell
chromedriver --url-base=/wd/hub --port=4444
```

or 

```shell
xvfb-run java -Dwebdriver.chrome.driver=/usr/bin/chromedriver -jar selenium-server-standalone-3.13.0.jar
```

## Firefox

TODO

## IE

In a `cmd` of a Windows 7/8/10 client run

    java -Dwebdriver.ie.driver=C:\Path\to\IEDriverServer.exe -jar C:\Path\to\selenium-server-standalone-<version>.jar

* `<version>` being `<=3.4`
* [IEDriverServer.exe](https://www.seleniumhq.org/download/) (use the 32-bit version, 64-bit is very slow in form inputs)

## Edge

In a `cmd` of a Windows 10 client run

    java -Dwebdriver.edge.driver=C:\Path\to\msedgedriver.exe -jar C:\Path\to\selenium-server-standalone-<version>.jar

* [msedgedriver.exe](https://developer.microsoft.com/en-us/microsoft-edge/tools/webdriver/)

## Run the tests

* Now you can run the tests with the following commands:

```shell
# run all available tests
codecept run
# run acceptance tests
codecept run acceptance --env <env>
# run functional tests
codecept run functional
# run unit tests
codecept run unit
```

where `<env>` can be of value:

* `chrome`: local chrome with [chromedriver](https://sites.google.com/a/chromium.org/chromedriver/downloads)
* `ff`: local firefox with [geckodriver](https://github.com/mozilla/geckodriver/releases)
* `ie`: remote ie on a Windows client
* `edge`: remote edge on a Windows client
* `saucelabs`: test via [saucelabs](https://saucelabs.com/)
