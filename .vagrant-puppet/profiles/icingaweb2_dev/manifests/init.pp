class icingaweb2_dev {
  mysql::database::create { 'icingaweb':
    username   => 'icingaweb',
    password   => 'icingaweb',
    privileges => 'ALL',
  }

  pgsql::database::create { 'icingaweb':
    username => 'icingaweb',
    password => 'icinga',
  }

  exec { 'populate-icingaweb-mysql-db-accounts':
    unless  => 'mysql -uicingaweb -picingaweb icingaweb -e "SELECT * FROM account;" &> /dev/null',
    command => 'mysql -uicingaweb -picingaweb icingaweb < /vagrant/etc/schema/accounts.mysql.sql',
    require => [ Exec['create-mysql-icingaweb-db'] ]
  }

  exec { 'populate-icingweba-pgsql-db-accounts':
    unless  => 'psql -U icingaweb -d icingaweb -c "SELECT * FROM account;" &> /dev/null',
    command => 'sudo -u postgres psql -U icingaweb -d icingaweb -f /vagrant/etc/schema/accounts.pgsql.sql',
    require => [ Exec['create-pgsql-icingaweb-db'] ]
  }

  exec { 'populate-icingaweb-mysql-db-preferences':
    unless  => 'mysql -uicingaweb -picingaweb icingaweb -e "SELECT * FROM preference;" &> /dev/null',
    command => 'mysql -uicingaweb -picingaweb icingaweb < /vagrant/etc/schema/preferences.mysql.sql',
    require => [ Exec['create-mysql-icingaweb-db'] ]
  }

  exec { 'populate-icingweba-pgsql-db-preferences':
    unless  => 'psql -U icingaweb -d icingaweb -c "SELECT * FROM preference;" &> /dev/null',
    command => 'sudo -u postgres psql -U icingaweb -d icingaweb -f /vagrant/etc/schema/preferences.pgsql.sql',
    require => [ Exec['create-pgsql-icingaweb-db'] ]
  }

  file { '/etc/httpd/conf.d/icingaweb.conf':
    source    => 'puppet:////vagrant/.vagrant-puppet/files/etc/httpd/conf.d/icingaweb.conf',
    require   => Package['apache'],
    notify    => Service['apache'],
  }

  file { '/etc/icingaweb':
    ensure    => 'directory',
    owner     => 'apache',
    group     => 'apache'
  }

  file { '/etc/icingaweb/authentication.ini':
    source    => 'puppet:////vagrant/config/authentication.ini',
    owner     => 'apache',
    group     => 'apache',
    require   => File['/etc/icingaweb'],
  }

  file { '/etc/icingaweb/config.ini':
    ensure    => file,
    owner     => 'apache',
    group     => 'apache',
  }

  file { '/etc/icingaweb/menu.ini':
    source    => 'puppet:////vagrant/config/menu.ini',
    owner     => 'apache',
    group     => 'apache',
  # replace   => false,
  }

  file { '/etc/icingaweb/resources.ini':
    source    => 'puppet:////vagrant/config/resources.ini',
    owner     => 'apache',
    group     => 'apache',
    replace   => false
  }

  file { ['/etc/icingaweb/enabledModules', '/etc/icingaweb/modules', '/etc/icingaweb/modules/monitoring', '/etc/icingaweb/modules/doc']:
    ensure    => 'directory',
    owner     => 'apache',
    group     => 'apache',
  }

  file { '/etc/icingaweb/modules/monitoring/backends.ini':
    source    => 'puppet:////vagrant/.vagrant-puppet/files/etc/icingaweb/modules/monitoring/backends.ini',
    owner     => 'apache',
    group     => 'apache',
  }

  file { '/etc/icingaweb/modules/monitoring/config.ini':
    source    => 'puppet:////vagrant/config/modules/monitoring/config.ini',
    owner     => 'apache',
    group     => 'apache',
  }

  file { '/etc/icingaweb/modules/monitoring/instances.ini':
    source    => 'puppet:////vagrant/config/modules/monitoring/instances.ini',
    owner     => 'apache',
    group     => 'apache',
  }

  file { '/etc/icingaweb/modules/monitoring/menu.ini':
    source    => 'puppet:////vagrant/config/modules/monitoring/menu.ini',
    owner     => 'apache',
    group     => 'apache',
  }

  file { '/etc/icingaweb/dashboard':
    ensure    => 'directory',
    owner     => 'apache',
    group     => 'apache',
  }

  file { '/etc/icingaweb/dashboard/dashboard.ini':
    source    => 'puppet:////vagrant/config/dashboard/dashboard.ini',
    owner     => 'apache',
    group     => 'apache',
  }

  file { '/etc/icingaweb/modules/doc/menu.ini':
    source    => 'puppet:////vagrant/config/modules/doc/menu.ini',
    owner     => 'apache',
    group     => 'apache',
  }
}
