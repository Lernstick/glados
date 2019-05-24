# Testing

This directory contains various tests for the basic application.

* Create `exam_tests` database and update it by applying migrations (you may skip this step if you do not have created any migrations yet). In the installation directory of glados (usually `/usr/share/glados`) run:

```shell
tests/bin/yii migrate
```

 * In order to be able to run acceptance tests you need to start a webserver.

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

* Now you can run the tests with the following commands:

```shell
# run all available tests
codecept run
# run acceptance tests
codecept run acceptance
# run functional tests
codecept run functional
# run unit tests
codecept run unit
```
