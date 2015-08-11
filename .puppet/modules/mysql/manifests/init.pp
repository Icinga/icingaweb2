# Class: mysql
#
#   This class installs the MySQL server and client software on a RHEL or CentOS
#
# Parameters:
#
# Actions:
#
# Requires:
#
# Sample Usage:
#
#   include mysql
#
class mysql {

  Exec { path => '/usr/bin' }

  if versioncmp($::operatingsystemmajrelease, '7') >= 0 {
    $client_package_name = 'mariadb'
    $server_package_name = 'mariadb-server'
    $server_service_name = 'mariadb'
    $cnf                 = '/etc/my.cnf.d/server.cnf'
    $log_error           = '/var/log/mariadb/mariadb.log'
    $pid_file            = '/var/run/mariadb/mariadb.pid'
  } else {
    $client_package_name = 'mysql'
    $server_package_name = 'mysql-server'
    $server_service_name = 'mysqld'
    $cnf                 = '/etc/my.cnf'
    $log_error           = '/var/log/mysqld.log'
    $pid_file            = '/var/run/mysqld/mysqld.pid'
  }

  package { [
    $client_package_name, $server_package_name,
  ]:
      ensure => latest,
  }

  service { $server_service_name:
    alias   => 'mysqld',
    enable  => true,
    ensure  => running,
    require => Package[$server_package_name],
  }

  file { $cnf:
    content => template('mysql/my.cnf.erb'),
    notify  => Service['mysqld'],
    recurse => true,
    require => Package[$server_package_name],
  }
}
