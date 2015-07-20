# Class: icinga2_dev
#
#   This class installs Icinga 2 w/ MySQL and PostgreSQL and provides Icinga 2 test configuration.
#
# Requires:
#
#   icinga2_mysql
#   icinga2::config
#   icinga2::feature
#
# Sample Usage:
#
#   include icinga2_dev
#
class icinga2_dev {
  include icinga2_mysql
  include icinga2_pgsql
  include monitoring_plugins
  include monitoring_test_config

  icinga2::config { [
    'conf.d/test-config', 'conf.d/commands', 'constants',
  ]:
    source => 'puppet:///modules/icinga2_dev',
  }

  icinga2::feature { 'ido-pgsql':
    ensure  => absent,
    require => Class['icinga2_pgsql'],
  }
}
