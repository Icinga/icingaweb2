class icinga2_dev {
  include icinga2
  include icinga2_mysql

  define icinga2_config {
    $path = "/etc/icinga2/${name}.conf"
    file { $path:
      source  => "puppet:///modules/icinga2_dev${path}",
      owner   => 'icinga',
      group   => 'icinga',
      require => Class['icinga2'],
    }
  }

  icinga2_config { [ 'conf.d/test-config', 'conf.d/commands', 'constants' ]: }
}
