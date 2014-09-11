class icingaweb2_dev {
  include apache
  include php

  class { 'zend_framework':
    notify => Service['apache'],
  }

  package { 'php-pdo':
    ensure => latest,
    notify => Service['apache'],
  }

  Exec { path => '/bin:/usr/bin' }

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
}
