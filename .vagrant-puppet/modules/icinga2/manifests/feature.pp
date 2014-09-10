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
define icinga2::feature {
  include icinga2

  exec { "icinga2-feature-${name}":
    path    => '/bin:/usr/bin:/sbin:/usr/sbin',
    unless  => "readlink /etc/icinga2/features-enabled/${name}.conf",
    command => "icinga2-enable-feature ${name}",
    notify  => Service['icinga2']
  }
}
