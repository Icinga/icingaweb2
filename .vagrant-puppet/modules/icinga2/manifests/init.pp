class icinga2 {
  include icinga_packages

  service { 'icinga2':
    ensure  => running,
    enable  => true,
    require => Package['icinga2']
  }

  package { [
    'icinga2', 'icinga2-doc', 'icinga2-debuginfo' ]:
    ensure => latest,
    require => Class['icinga_packages'],
  }

  icinga2::feature { [ 'statusdata', 'command', 'compatlog' ]: }
}
