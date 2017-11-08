#!/bin/bash

set -ex

ICINGAWEB_HOME=${ICINGAWEB_HOME:="$(dirname "$(readlink -f $(dirname "$0"))")"}
PHP_VERSION="$(php -r 'echo phpversion();')"
PHPCS_VERSION=${PHPCS_VERSION:=3.0.2}
MOCKERY_VERSION=${MOCKERY_VERSION:=0.9.9}
HAMCREST_VERSION=${HAMCREST_VERSION:=2.0.0}

if [ "$PHP_VERSION" '<' 5.6.0 ]; then
  PHPUNIT_VERSION=${PHPUNIT_VERSION:=4.8}
else
  PHPUNIT_VERSION=${PHPUNIT_VERSION:=5.7}
fi

cd ${ICINGAWEB_HOME}

test -d vendor || mkdir vendor

# phpunit
phpunit_path="vendor/phpunit-${PHPUNIT_VERSION}"
if [ ! -e "${phpunit_path}".phar ]; then
  wget -O "${phpunit_path}".phar https://phar.phpunit.de/phpunit-${PHPUNIT_VERSION}.phar
fi
ln -svf "${phpunit_path}".phar phpunit.phar

# phpcs
phpcs_path="vendor/phpcs-${PHPCS_VERSION}"
if [ ! -e "${phpcs_path}".phar ]; then
  wget -O "${phpcs_path}".phar \
    https://github.com/squizlabs/PHP_CodeSniffer/releases/download/${PHPCS_VERSION}/phpcs.phar
fi
ln -svf "${phpcs_path}".phar phpcs.phar
phpcbf_path="vendor/phpcbf-${PHPCS_VERSION}"
if [ ! -e "${phpcbf_path}".phar ]; then
  wget -O "${phpcbf_path}".phar \
    https://github.com/squizlabs/PHP_CodeSniffer/releases/download/${PHPCS_VERSION}/phpcbf.phar
fi
ln -svf "${phpcbf_path}".phar phpcbf.phar

# mockery
mockery_path="vendor/mockery-${MOCKERY_VERSION}"
if [ ! -e "${mockery_path}".tar.gz ]; then
  wget -O "${mockery_path}".tar.gz \
    https://github.com/mockery/mockery/archive/${MOCKERY_VERSION}.tar.gz
fi
if [ ! -d "${mockery_path}" ]; then
  tar xf "${mockery_path}".tar.gz -C vendor/
fi
ln -svf "${mockery_path}"/library/Mockery
ln -svf "${mockery_path}"/library/Mockery.php

# hamcrest
hamcrest_path="vendor/hamcrest-php-${HAMCREST_VERSION}"
if [ ! -e "${hamcrest_path}".tar.gz ]; then
  wget -O "${hamcrest_path}".tar.gz \
    https://github.com/hamcrest/hamcrest-php/archive/v${HAMCREST_VERSION}.tar.gz
fi
if [ ! -d "${hamcrest_path}" ]; then
  tar xf "${hamcrest_path}".tar.gz -C vendor/
fi
ln -svf "${hamcrest_path}"/hamcrest/Hamcrest
ln -svf "${hamcrest_path}"/hamcrest/Hamcrest.php
