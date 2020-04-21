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
#   epel
#   scl
#
# Sample Usage:
#
#   include php
#
class php {

  include apache
  include epel
  include scl

  package { 'rh-php73-php-fpm':
    ensure  => latest,
    notify  => Service['apache'],
    require => [ Class['scl'], Package['apache'] ],
  }
  -> service { 'rh-php73-php-fpm':
    ensure => running,
    enable => true,
    alias => 'php-fpm',
  }

  package { 'php-pecl-xdebug':
    ensure => latest,
    notify => Service['apache'],
    require => Class['epel'],
  }

  php::phpd { ['error_reporting', 'timezone', 'xdebug_settings' ]: }
}
