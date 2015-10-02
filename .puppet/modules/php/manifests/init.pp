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

  package { 'php':
    ensure  => latest,
    notify  => Service['apache'],
    require => Package['apache'],
  }

  php::phpd { ['error_reporting', 'timezone', 'xdebug_settings' ]: }
}
