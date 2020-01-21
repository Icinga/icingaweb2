#!/bin/bash

set -ex

ICINGAWEB_HOME=${ICINGAWEB_HOME:="$(dirname "$(readlink -f "$(dirname "$0")")")"}
PHPCS_VERSION=${PHPCS_VERSION:=3.5.3}
MOCKERY_VERSION=${MOCKERY_VERSION:=0.9.9}
HAMCREST_VERSION=${HAMCREST_VERSION:=2.0.0}
PHPUNIT_VERSION=${PHPUNIT_VERSION:=5.7}

cd "${ICINGAWEB_HOME}"

test -d vendor || mkdir vendor
cd vendor/

del_old_link() {
  if [ -L "$1" ]; then
    rm "$1"
  fi
}

# phpunit
phpunit_path="phpunit-${PHPUNIT_VERSION}"
if [ ! -e "${phpunit_path}".phar ]; then
  wget -O "${phpunit_path}".phar https://phar.phpunit.de/phpunit-${PHPUNIT_VERSION}.phar
fi
ln -svf "${phpunit_path}".phar phpunit.phar
del_old_link ../phpunit.phar

# phpcs
phpcs_path="phpcs-${PHPCS_VERSION}"
if [ ! -e "${phpcs_path}".phar ]; then
  wget -O "${phpcs_path}".phar \
    https://github.com/squizlabs/PHP_CodeSniffer/releases/download/${PHPCS_VERSION}/phpcs.phar
fi
ln -svf "${phpcs_path}".phar phpcs.phar
del_old_link ../phpcs.phar

phpcbf_path="phpcbf-${PHPCS_VERSION}"
if [ ! -e "${phpcbf_path}".phar ]; then
  wget -O "${phpcbf_path}".phar \
    https://github.com/squizlabs/PHP_CodeSniffer/releases/download/${PHPCS_VERSION}/phpcbf.phar
fi
ln -svf "${phpcbf_path}".phar phpcbf.phar
del_old_link ../phpcbf.phar

# mockery
mockery_path="mockery-${MOCKERY_VERSION}"
if [ ! -e "${mockery_path}".tar.gz ]; then
  wget -O "${mockery_path}".tar.gz \
    https://github.com/mockery/mockery/archive/${MOCKERY_VERSION}.tar.gz
fi
if [ ! -d "${mockery_path}" ]; then
  tar xf "${mockery_path}".tar.gz
fi
ln -svf "${mockery_path}"/library/Mockery Mockery
ln -svf "${mockery_path}"/library/Mockery.php Mockery.php
del_old_link ../Mockery
del_old_link ../Mockery.php

# hamcrest
hamcrest_path="hamcrest-php-${HAMCREST_VERSION}"
if [ ! -e "${hamcrest_path}".tar.gz ]; then
  wget -O "${hamcrest_path}".tar.gz \
    https://github.com/hamcrest/hamcrest-php/archive/v${HAMCREST_VERSION}.tar.gz
fi
if [ ! -d "${hamcrest_path}" ]; then
  tar xf "${hamcrest_path}".tar.gz
fi
ln -svf "${hamcrest_path}"/hamcrest/Hamcrest Hamcrest
ln -svf "${hamcrest_path}"/hamcrest/Hamcrest.php Hamcrest.php
del_old_link ../Hamcrest
del_old_link ../Hamcrest.php
