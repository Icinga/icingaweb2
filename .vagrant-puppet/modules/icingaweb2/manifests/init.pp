class icingaweb2 {
  include apache

  file { 'icingaweb2cfgDir':
    path    => '/etc/icingaweb',
    ensure  => directory,
    links   => follow,
    owner   => 'apache',
    group   => 'apache',
    mode    => 6755,
    require => Class['apache'],
  }
}
