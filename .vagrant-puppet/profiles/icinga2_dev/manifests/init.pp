class icinga2_dev {
  include icinga2
  include icinga2_mysql

  icinga2::config { [
    'conf.d/test-config', 'conf.d/commands', 'constants' ]:
    source => 'puppet:///modules/icinga2_dev',
  }
}
