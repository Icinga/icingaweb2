# Class: php_imagick
#
#   This class installs the ImageMagick PHP module.
#
# Parameters:
#
# Actions:
#
# Requires:
#
#   php
#
# Sample Usage:
#
#   include php_imagick
#
class php_imagick {
  include php

  $php_imagick = $::operatingsystem ? {
    /(Debian|Ubuntu)/        => 'php5-imagick',
    /(RedHat|CentOS|Fedora)/ => 'php-pecl-imagick',
    /(SLES|OpenSuSE)/        => 'php5-imagick',
  }

  package { $php_imagick:
    ensure => latest,
  }
}
