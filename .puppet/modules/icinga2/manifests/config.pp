# Define: icinga2::config
#
#   Provide Icinga 2 configuration file
#
# Parameters:
#
#   [*source*] - where to take the file from
#
# Requires:
#
#   icinga2
#
# Sample Usage:
#
#   icinga2::config { 'constants';
#     source => 'puppet:///modules/icinga2_dev',
#   }
#
#   Provide configuration file '/etc/icinga2/constants.conf'
#   from 'puppet:///modules/icinga2_dev/constants.conf'
#   ('/path/to/puppet/modules/icinga2_dev/files/constants.conf')
#
define icinga2::config ($source) {
  include icinga2

  $path = "/etc/icinga2/${name}.conf"

  parent_dirs { $path:
    user    => 'icinga',
    require => [
      User['icinga'],
      File['icinga2cfgDir']
    ],
  }
  -> file { $path:
    source  => "${source}/${name}.conf",
    owner   => 'icinga',
    group   => 'icinga',
    notify  => Service['icinga2'],
    require => User['icinga'],
  }
}
