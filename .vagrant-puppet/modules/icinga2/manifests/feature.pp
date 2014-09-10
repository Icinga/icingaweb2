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

  if $source != undef {
    icinga2::config { "features-available/${name}":
      source => $source,
    }
  }

  file { "/etc/icinga2/features-enabled/${name}.conf":
    ensure => link,
    target => "/etc/icinga2/features-available/${name}.conf",
    notify => Service['icinga2'],
  }
}
