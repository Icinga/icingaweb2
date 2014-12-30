class motd {
  file { '/etc/motd':
    source  => 'puppet:///modules/motd/motd',
    owner   => root,
    group   => root,
  }
}
