define icinga2::config ($source) {
  include icinga2

  $path = "/etc/icinga2/${name}.conf"
  file { $path:
    source  => "${source}${path}",
    owner   => 'icinga',
    group   => 'icinga',
    require => Class['icinga2'],
  }
}
