class icinga2-mysql {
  include icinga-packages
  include icinga2

  mysql::database::populate { 'icinga2':
    username => 'icinga2',
    password => 'icinga2',
    privileges => 'SELECT,INSERT,UPDATE,DELETE',
    schemafile => '/usr/share/icinga2-ido-mysql/schema/mysql.sql',
    require => Package['icinga2-ido-mysql'],
  }

  icinga2::feature { 'ido-mysql':
    require => Mysql::Database::Populate['icinga2'],
  }

  package { 'icinga2-ido-mysql':
    ensure => latest,
    require => Class['icinga-packages'],
  }

  file { '/etc/icinga2/features-available/ido-mysql.conf':
    source  => 'puppet:////vagrant/.vagrant-puppet/files/etc/icinga2/features-available/ido-mysql.conf',
    owner   => 'icinga',
    group   => 'icinga',
    notify => Service['icinga2'],
  }

  file { '/etc/icinga2/features-enabled/ido-mysql.conf':
    ensure  => 'link',
    target  => '/etc/icinga2/features-available/ido-mysql.conf',
    owner   => 'root',
    group   => 'root',
    require => Package['icinga2-ido-mysql'],
  }
}
