define monitoring_test_config::populate_plugins {
  include icinga2
  include monitoring_plugins
  include monitoring_test_config

  file { "/usr/lib64/nagios/plugins/${name}":
    owner   => 'icinga',
    group   => 'icinga',
    source  => "/usr/local/share/misc/monitoring_test_config/plugins/${name}",
    require => [
      User['icinga'],
      Exec['create_monitoring_test_config'],
      Class['monitoring_plugins']
    ],
    notify => Service['icinga2'],
  }
}
