# Class: icinga2_mysql
#
#   This class installs Icinga 2 and Icinga-2-IDO-MySQL and set up the database for the last one.
#
# Requires:
#
#   icinga2
#   icinga_packages
#   icinga2::feature
#   mysql::database::populate
#
# Sample Usage:
#
#   include icinga2_mysql
#
class icinga2_mysql {
  include icinga2
  include icinga_packages

  package { 'icinga2-ido-mysql':
    ensure  => latest,
    require => Class['icinga_packages'],
  }

  mysql::database::populate { 'icinga2':
    username   => 'icinga2',
    password   => 'icinga2',
    privileges => 'SELECT,INSERT,UPDATE,DELETE',
    schemafile => '/usr/share/icinga2-ido-mysql/schema/mysql.sql',
    require    => Package['icinga2-ido-mysql'],
  }

  file { '/etc/icinga2/features-available/ido-mysql.conf':
    source  => 'puppet:///modules/icinga2_mysql/etc/icinga2/features-available/ido-mysql.conf',
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
