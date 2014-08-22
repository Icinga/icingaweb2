class profile::icinga2-dev ($icinga2Version) {
  include icinga2-mysql

  icinga2::feature { [ 'statusdata', 'command', 'compatlog' ]:
    require => Package['icinga2-classicui-config'],
  }

  file { '/etc/icinga2/conf.d/test-config.conf':
    source  => 'puppet:////vagrant/.vagrant-puppet/files/etc/icinga2/conf.d/test-config.conf',
    owner   => 'icinga',
    group   => 'icinga',
    require => [ Package['icinga2'], Exec['create_monitoring_test_config'] ]
  }

  file { '/etc/icinga2/conf.d/commands.conf':
    source  => 'puppet:////vagrant/.vagrant-puppet/files/etc/icinga2/conf.d/commands.conf',
    owner   => 'icinga',
    group   => 'icinga',
    require => Package['icinga2'],
  }

  file { '/etc/icinga2/constants.conf':
    source  => 'puppet:////vagrant/.vagrant-puppet/files/etc/icinga2/constants.conf',
    owner   => 'icinga',
    group   => 'icinga',
    require => Package['icinga2'],
  }
}
