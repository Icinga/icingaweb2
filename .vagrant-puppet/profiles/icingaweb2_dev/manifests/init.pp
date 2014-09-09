class icingaweb2_dev {
  mysql::database::populate { 'icingaweb':
    username   => 'icingaweb',
    password   => 'icingaweb',
    privileges => 'ALL',
    schemafile => '/vagrant/etc/schema/accounts.mysql.sql',
  }

  pgsql::database::populate { 'icingaweb':
    username => 'icingaweb',
    password => 'icinga',
    schemafile => '/vagrant/etc/schema/accounts.pgsql.sql',
  }

  exec { 'populate-icingaweb-mysql-db-preferences':
    unless  => 'mysql -uicingaweb -picingaweb icingaweb -e "SELECT * FROM preference;" &> /dev/null',
    command => 'mysql -uicingaweb -picingaweb icingaweb < /vagrant/etc/schema/preferences.mysql.sql',
    require => Mysql::Database::Populate['icingaweb'],
  }

  exec { 'populate-icingweb-pgsql-db-preferences':
    unless  => 'psql -U icingaweb -d icingaweb -c "SELECT * FROM preference;" &> /dev/null',
    command => 'sudo -u postgres psql -U icingaweb -d icingaweb -f /vagrant/etc/schema/preferences.pgsql.sql',
    require => Pgsql::Database::Populate['icingaweb'],
  }

  file { '/etc/httpd/conf.d/icingaweb.conf':
    source    => 'puppet:////vagrant/.vagrant-puppet/files/etc/httpd/conf.d/icingaweb.conf',
    require   => Package['apache'],
    notify    => Service['apache'],
  }

  $cfgpath = '/etc/icingaweb'

  file { "${cfgpath}/config.ini":
    ensure    => file,
    owner     => 'apache',
    group     => 'apache',
  }

  file { [
    "${cfgpath}",
    "${cfgpath}/enabledModules",
    "${cfgpath}/modules",
    "${cfgpath}/modules/monitoring",
    "${cfgpath}/modules/doc"
  ]:
    ensure    => 'directory',
    owner     => 'apache',
    group     => 'apache',
  }

  icingaweb2::config { 'authentication': }

  icingaweb2::config { 'resources':
    replace   => false,
  }

  icingaweb2::config::module { [ 'backends', 'config', 'instances' ]: }
}
