class icinga2 {
  service { 'icinga2':
    ensure  => running,
    require => [
    Package['icinga2'],
    File['/etc/icinga2/features-enabled/ido-mysql.conf'],
    File['/etc/icinga2/conf.d/test-config.conf'],
    File['/etc/icinga2/conf.d/commands.conf']
    ]
  }

  package { 'icinga2':
    ensure => latest,
    require => Yumrepo['icinga2-repo'],
    alias => 'icinga2'
  }

  package { 'icinga2-bin':
    ensure => latest,
    require => [ Yumrepo['icinga2-repo'], Package['icinga2'] ],
    alias => 'icinga2-bin'
  }

  package { 'icinga2-doc':
    ensure => latest,
    require => Yumrepo['icinga2-repo'],
    alias => 'icinga2-doc'
  }

  package { 'icinga2-classicui-config':
    ensure => latest,
    before => Package["icinga-gui"],
    require => [ Yumrepo['icinga2-repo'], Package['icinga2'] ],
    notify => Service['apache'],
  }
}
