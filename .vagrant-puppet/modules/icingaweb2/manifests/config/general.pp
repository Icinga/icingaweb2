define icingaweb2::config::general ($source, $replace = true) {
  include apache
  include icingaweb2

  $path = "/etc/icingaweb/${name}.ini"

  parent_dirs { $path:
    require => File['icingaweb2cfgDir'],
  }
  -> file { $path:
    source  => "${source}/${name}.ini",
    owner   => 'apache',
    group   => 'apache',
    replace => $replace,
    require => Class['apache'],
  }
}
