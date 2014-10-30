class icingaweb2_dev {
  include apache
  include php
  include icingaweb2
  include icingacli

  class { 'zend_framework':
    notify => Service['apache'],
  }

  package { [ 'php-pdo', 'php-ldap', 'php-phpunit-PHPUnit', 'icinga-gui' ]:
    ensure => latest,
    notify => Service['apache'],
  }

  Exec { path => '/usr/local/bin:/usr/bin:/bin' }

  file { '/etc/icingaweb/enabledModules':
    ensure  => directory,
    owner   => 'apache',
    group   => 'apache',
    mode    => 6755,
    require => [
      Class['apache'],
      File['icingaweb2cfgDir']
    ],
  }
  -> exec { 'enable-monitoring-module':
    command => 'icingacli module enable monitoring',
    user    => 'apache',
    require => Class[[ 'icingacli', 'apache' ]],
  }
  -> exec { 'enable-test-module':
    command => 'icingacli module enable test',
    user    => 'apache'
  }

  group { 'icingacmd':
    ensure => present,
  }
  -> exec { 'usermod -aG icingacmd apache':
    command => '/usr/sbin/usermod -aG icingacmd apache',
    require => [
      Class['icingacli'],
      User['apache']
    ],
    notify  => Service['apache'],
  }

  file { '/var/log/icingaweb.log':
    ensure  => file,
    owner   => 'apache',
    group   => 'apache',
    require => Class['apache'],
  }

  mysql::database::populate { 'icingaweb':
    username   => 'icingaweb',
    password   => 'icingaweb',
    privileges => 'ALL',
    schemafile => '/vagrant/etc/schema/accounts.mysql.sql',
  }

  pgsql::database::populate { 'icingaweb':
    username => 'icingaweb',
    password => 'icingaweb',
    schemafile => '/vagrant/etc/schema/accounts.pgsql.sql',
  }

  exec { 'populate-icingaweb-mysql-db-preferences':
    unless  => 'mysql -uicingaweb -picingaweb icingaweb -e "SELECT * FROM preference;" &> /dev/null',
    command => 'mysql -uicingaweb -picingaweb icingaweb < /vagrant/etc/schema/preferences.mysql.sql',
    require => Mysql::Database::Populate['icingaweb'],
  }

  exec { 'populate-icingweb-pgsql-db-preferences':
    unless  => 'psql -U icingaweb -d icingaweb -c "SELECT * FROM preference;" &> /dev/null',
    command => 'psql -U icingaweb -d icingaweb -f /vagrant/etc/schema/preferences.pgsql.sql',
    user    => 'postgres',
    require => Pgsql::Database::Populate['icingaweb'],
  }

  file { '/etc/httpd/conf.d/icingaweb.conf':
    source    => 'puppet:////vagrant/.vagrant-puppet/files/etc/httpd/conf.d/icingaweb.conf',
    notify    => Service['apache'],
  }

  icingaweb2::config::general { 'authentication':
    source  => 'puppet:///modules/icingaweb2_dev',
  }

  icingaweb2::config::general { [ 'resources', 'config' ]:
    source  => 'puppet:///modules/icingaweb2_dev',
    replace => false,
  }

  icingaweb2::config::module { [ 'backends', 'config', 'instances' ]:
    source  => 'puppet:///modules/icingaweb2_dev',
  }

  package { 'iptables':
    ensure => latest
  }
  -> exec { 'iptables-allow-http':
    unless  => 'grep -Fxqe "-A INPUT -p tcp -m state --state NEW -m tcp --dport 80 -j ACCEPT" /etc/sysconfig/iptables',
    command => '/sbin/iptables -I INPUT 1 -p tcp -m state --state NEW -m tcp --dport 80 -j ACCEPT && /sbin/iptables-save > /etc/sysconfig/iptables'
  }
}
