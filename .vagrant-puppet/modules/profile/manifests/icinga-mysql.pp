class profile::icinga-mysql ($icingaVersion) {
  cmmi { 'icinga-mysql':
    url     => "https://github.com/Icinga/icinga-core/releases/download/v${icingaVersion}/icinga-${icingaVersion}.tar.gz",
    output  => "icinga-${icingaVersion}.tar.gz",
    flags   => '--prefix=/usr/local/icinga-mysql --with-command-group=icinga-cmd \
                --enable-idoutils --with-init-dir=/usr/local/icinga-mysql/etc/init.d \
                --with-htmurl=/icinga-mysql --with-httpd-conf-file=/etc/httpd/conf.d/icinga-mysql.conf \
                --with-cgiurl=/icinga-mysql/cgi-bin \
                --with-http-auth-file=/usr/share/icinga/htpasswd.users \
                --with-plugin-dir=/usr/lib64/nagios/plugins/libexec',
    creates => '/usr/local/icinga-mysql',
    make    => 'make all && make fullinstall install-config',
    require => [ User['icinga'], Exec['install nagios-plugins-all'], Package['apache'] ],
    notify  => Service['apache'],
  }

  file { '/etc/init.d/icinga-mysql':
    source  => '/usr/local/icinga-mysql/etc/init.d/icinga',
    require => Cmmi['icinga-mysql'],
  }

  file { '/etc/init.d/ido2db-mysql':
    source  => '/usr/local/icinga-mysql/etc/init.d/ido2db',
    require => Cmmi['icinga-mysql'],
  }

  service { 'icinga-mysql':
    ensure  => running,
    require => File['/etc/init.d/icinga-mysql'],
  }

  service { 'ido2db-mysql':
    ensure  => running,
    require => File['/etc/init.d/ido2db-mysql'],
  }

  file { '/usr/local/icinga-mysql/etc/ido2db.cfg':
    content => template('icinga/ido2db-mysql.cfg.erb'),
    owner   => 'icinga',
    group   => 'icinga',
    require => Cmmi['icinga-mysql'],
    notify  => [ Service['icinga-mysql'], Service['ido2db-mysql'] ]
  }

  file { '/usr/local/icinga-mysql/etc/idomod.cfg':
    source  => '/usr/local/icinga-mysql/etc/idomod.cfg-sample',
    owner   => 'icinga',
    group   => 'icinga',
    require => Cmmi['icinga-mysql'],
    notify  => [ Service['icinga-mysql'], Service['ido2db-mysql'] ]
  }

  file { '/usr/local/icinga-mysql/etc/modules/idoutils.cfg':
    source  => '/usr/local/icinga-mysql/etc/modules/idoutils.cfg-sample',
    owner   => 'icinga',
    group   => 'icinga',
    require => Cmmi['icinga-mysql'],
    notify  => [ Service['icinga-mysql'], Service['ido2db-mysql'] ]
  }

  mysql::database::populate { 'icinga':
    username => 'icinga',
    password => 'icinga',
    privileges => 'SELECT,INSERT,UPDATE,DELETE',
    schemafile => "/usr/local/src/icinga-mysql/icinga-${icingaVersion}/module/idoutils/db/mysql/mysql.sql",
    requirement => Cmmi['icinga-mysql'],
  }
}
