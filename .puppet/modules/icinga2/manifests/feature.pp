# Define: icinga2::feature
#
#   Enable Icinga 2 feature
#
# Requires:
#
#   icinga2
#
# Sample Usage:
#
#   icinga2::feature { 'example-feature'; }
#
define icinga2::feature ($source = undef) {
  include icinga2

  $target = "features-available/${name}"
  $cfgpath = '/etc/icinga2'
  $path = "${cfgpath}/features-enabled/${name}.conf"

  if $source != undef {
    icinga2::config { $target:
      source => $source,
    }
  }

  parent_dirs { $path:
    user    => 'icinga',
    require => [
      User['icinga'],
      File['icinga2cfgDir']
    ],
  }
  -> file { $path:
    ensure  => link,
    target  => "${cfgpath}/${target}.conf",
    notify  => Service['icinga2'],
  }
}
