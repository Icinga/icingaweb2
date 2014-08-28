class icinga2-mysql {
  include icinga-packages

  package { 'icinga2-ido-mysql':
    ensure => latest,
    require => Class['icinga-packages'],
  }

  mysql::database::populate { 'icinga2':
    username => 'icinga2',
    password => 'icinga2',
    privileges => 'SELECT,INSERT,UPDATE,DELETE',
    schemafile => '/usr/share/icinga2-ido-mysql/schema/mysql.sql',
    require => Package['icinga2-ido-mysql'],
  }

  file { '/etc/icinga2/features-available/ido-mysql.conf':
    source  => 'puppet:///modules/icinga2-mysql/etc/icinga2/features-available/ido-mysql.conf',
    owner   => 'icinga',
    group   => 'icinga',
  }

  icinga2::feature { 'ido-mysql':
    require => [
      Mysql::Database::Populate['icinga2'],
      File['/etc/icinga2/features-available/ido-mysql.conf']
    ],
  }
}
