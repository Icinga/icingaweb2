# Class: php
#
#   This class installs php.
#
# Parameters:
#
# Actions:
#
# Requires:
#
#   apache
#
# Sample Usage:
#
#   include php
#
class php {

  include apache
  include epel

  package { 'php':
    ensure  => latest,
    notify  => Service['apache'],
    require => Package['apache'],
  }

  package { 'php-pecl-xdebug':
    ensure => latest,
    notify => Service['apache'],
    require => Class['epel'],
  }

  php::phpd { ['error_reporting', 'timezone', 'xdebug_settings' ]: }
}
