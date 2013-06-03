# Class: php
#
#   This class installs the php and php-mysql packages.
#
# Parameters:
#
# Actions:
#
# Requires:
#
# Sample Usage:
#
#   include php
#
class php {

  package { 'php':
    ensure  => installed,
    require => Package["${apache::apache}"]
  }

  package { 'php-mysql':
    ensure  => installed,
    require => Package['mysql-server']
  }
}
