class profile::icinga2 ($icinga2Version) {
  mysql::database::populate { 'icinga2':
    username => 'icinga2',
    password => 'icinga2',
    privileges => 'SELECT,INSERT,UPDATE,DELETE',
    schemafile => "/usr/share/doc/icinga2-ido-mysql-${icinga2Version}/schema/mysql.sql",
    requirement => Package['icinga2-ido-mysql'],
  }

  define icinga2::feature ($feature = $title) {
    exec { "icinga2-feature-${feature}":
      path => '/bin:/usr/bin:/sbin:/usr/sbin',
      unless => "readlink /etc/icinga2/features-enabled/${feature}.conf",
      command => "icinga2-enable-feature ${feature}",
      require => [ Package['icinga2'] ],
      notify => Service['icinga2']
    }
  }

  icinga2::feature { [ 'statusdata', 'command', 'compatlog' ]:
    require => Package['icinga2-classicui-config'],
  }

  icinga2::feature { 'ido-mysql':
    require => Exec['populate-icinga2-mysql-db'],
  }

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

  package { 'icinga2-ido-mysql':
    ensure => latest,
    require => Yumrepo['icinga2-repo'],
    alias => 'icinga2-ido-mysql'
  }


  file { '/etc/icinga2/features-available/ido-mysql.conf':
    source  => 'puppet:////vagrant/.vagrant-puppet/files/etc/icinga2/features-available/ido-mysql.conf',
    owner   => 'icinga',
    group   => 'icinga',
    require => Package['icinga2'],
    notify => Service['icinga2'],
  }

  file { '/etc/icinga2/features-enabled/ido-mysql.conf':
    ensure  => 'link',
    target  => '/etc/icinga2/features-available/ido-mysql.conf',
    owner   => 'root',
    group   => 'root',
    require => Package['icinga2-ido-mysql'],
  }

  file { '/etc/icinga2/conf.d/test-config.conf':
    source  => 'puppet:////vagrant/.vagrant-puppet/files/etc/icinga2/conf.d/test-config.conf',
    owner   => 'icinga',
    group   => 'icinga',
    require => [ Package['icinga2'], Exec['create_monitoring_test_config'] ]
  }

  file { '/etc/icinga2/conf.d/commands.conf':
    source  => 'puppet:////vagrant/.vagrant-puppet/files/etc/icinga2/conf.d/commands.conf',
    owner   => 'icinga',
    group   => 'icinga',
    require => Package['icinga2'],
  }

  file { '/etc/icinga2/constants.conf':
    source  => 'puppet:////vagrant/.vagrant-puppet/files/etc/icinga2/constants.conf',
    owner   => 'icinga',
    group   => 'icinga',
    require => Package['icinga2'],
  }
}
