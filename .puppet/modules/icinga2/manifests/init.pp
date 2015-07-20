# Class: icinga2
#
#   This class installs Icinga 2.
#
# Requires:
#
#   icinga_packages
#   icinga2::feature
#
# Sample Usage:
#
#   include icinga2
#
class icinga2 {
  include icinga_packages

  package { [
    'icinga2', 'icinga2-doc', 'icinga2-debuginfo'
  ]:
    ensure  => latest,
    require => Class['icinga_packages'],
  }
  -> service { 'icinga2':
    ensure  => running,
    enable  => true,
    require => User['icinga'],
  }

  user { 'icinga':
    ensure => present,
  }
  -> file { 'icinga2cfgDir':
    path   => '/etc/icinga2',
    ensure => directory,
    links  => follow,
    owner  => 'icinga',
    group  => 'icinga',
    mode   => 6750,
  }

  icinga2::feature { [ 'statusdata', 'command', 'compatlog' ]: }

  icinga2::feature { 'api':
    ensure => absent,
  }
}
