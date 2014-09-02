define icinga2::feature ($feature = $title) {
  include icinga2

  exec { "icinga2-feature-${feature}":
    path    => '/bin:/usr/bin:/sbin:/usr/sbin',
    unless  => "readlink /etc/icinga2/features-enabled/${feature}.conf",
    command => "icinga2-enable-feature ${feature}",
    require => Package['icinga2'],
    notify  => Service['icinga2']
  }
}
