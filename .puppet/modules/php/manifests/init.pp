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
    require => Package['apache'],
    notify  => Service['apache']
  }
  # TODO(el): Always executed. Should be a resource
  -> exec { 'php-timezone':
    command => 'sed -re $\'s#^;?(date\\.timezone =).*$#\\1 "UTC"#\' -i /etc/php.ini',
    notify  => Service['apache'],
  }

  file { '/etc/php.d/error_reporting.ini':
    content => template('php/error_reporting.ini.erb'),
    require => Package['php'],
    notify  => Service['apache']
  }

  file { '/etc/php.d/xdebug_settings.ini':
    content => template('php/xdebug_settings.ini.erb'),
    require => Package['php'],
    notify  => Service['apache']
  }
}
