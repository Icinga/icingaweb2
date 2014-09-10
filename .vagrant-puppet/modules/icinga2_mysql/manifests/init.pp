# Class: icinga2_mysql
#
#   This class installs Icinga 2 and Icinga-2-IDO-MySQL and set up the database for the last one.
#
# Requires:
#
#   icinga_packages
#   icinga2
#   icinga2::feature
#   icinga2::config
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

  icinga2::config { 'features-available/ido-mysql':
    source => 'puppet:///modules/icinga2_mysql',
  }

  icinga2::feature { 'ido-mysql':
    require => [
      Mysql::Database::Populate['icinga2'],
      File['/etc/icinga2/features-available/ido-mysql.conf']
    ],
  }
}
