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
define icinga2::feature ($ensure = 'present') {
  include icinga2

  $action = $ensure ? {
    /(present)/ => 'enable',
    /(absent)/  => 'disable',
  }
  $test = $ensure ? {
    /(present)/ => '-e',
    /(absent)/  => '! -e',
  }

  exec { "icinga2-feature-${action}-${name}":
    unless  => "/usr/bin/test ${test} /etc/icinga2/features-enabled/${name}.conf",
    command => "/usr/sbin/icinga2 feature ${action} ${name}",
    require => Package['icinga2'],
    notify  => Service['icinga2'],
  }
}
