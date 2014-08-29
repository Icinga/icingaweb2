class icinga2-dev {
  include icinga2
  include icinga2-mysql

  define icinga2-config {
    $path = "/etc/icinga2/${name}.conf"
    file { $path:
      source  => "puppet:///modules/icinga2-dev${path}",
      owner   => 'icinga',
      group   => 'icinga',
      require => Class['icinga2'],
    }
  }

  icinga2-config { [ 'conf.d/test-config', 'conf.d/commands', 'constants' ]: }
}
