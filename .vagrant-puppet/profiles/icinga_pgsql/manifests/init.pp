class icinga_pgsql ($icingaVersion) {
  cmmi { 'icinga-pgsql':
    url     => "https://github.com/Icinga/icinga-core/releases/download/v${icingaVersion}/icinga-${icingaVersion}.tar.gz",
    output  => "icinga-${icingaVersion}.tar.gz",
    flags   => '--prefix=/usr/local/icinga-pgsql \
                --with-command-group=icinga-cmd --enable-idoutils \
                --with-init-dir=/usr/local/icinga-pgsql/etc/init.d \
                --with-htmurl=/icinga-pgsql --with-httpd-conf-file=/etc/httpd/conf.d/icinga-pgsql.conf \
                --with-cgiurl=/icinga-pgsql/cgi-bin \
                --with-http-auth-file=/usr/share/icinga/htpasswd.users \
                --with-plugin-dir=/usr/lib64/nagios/plugins/libexec',
    creates => '/usr/local/icinga-pgsql',
    make    => 'make all && make fullinstall install-config',
    require => [ User['icinga'], Exec['install nagios-plugins-all'], Package['apache'] ],
    notify  => Service['apache'],
  }

  file { '/etc/init.d/icinga-pgsql':
    source  => '/usr/local/icinga-pgsql/etc/init.d/icinga',
    require => Cmmi['icinga-pgsql'],
  }

  file { '/etc/init.d/ido2db-pgsql':
    source  => '/usr/local/icinga-pgsql/etc/init.d/ido2db',
    require => Cmmi['icinga-pgsql'],
  }

  service { 'icinga-pgsql':
    ensure  => running,
    require => Cmmi['icinga-pgsql'],
  }

  service { 'ido2db-pgsql':
    ensure  => running,
    require => Cmmi['icinga-pgsql'],
  }

  file { '/usr/local/icinga-pgsql/etc/ido2db.cfg':
    content => template('icinga/ido2db-pgsql.cfg.erb'),
    owner   => 'icinga',
    group   => 'icinga',
    require => Cmmi['icinga-pgsql'],
    notify  => [ Service['icinga-pgsql'], Service['ido2db-pgsql'] ]
  }

  file { '/usr/local/icinga-pgsql/etc/idomod.cfg':
    source  => '/usr/local/icinga-pgsql/etc/idomod.cfg-sample',
    owner   => 'icinga',
    group   => 'icinga',
    require => Cmmi['icinga-pgsql'],
    notify  => [ Service['icinga-pgsql'], Service['ido2db-pgsql'] ]
  }

  file { '/usr/local/icinga-pgsql/etc/modules/idoutils.cfg':
    source  => '/usr/local/icinga-pgsql/etc/modules/idoutils.cfg-sample',
    owner   => 'icinga',
    group   => 'icinga',
    require => Cmmi['icinga-pgsql'],
    notify  => [ Service['icinga-pgsql'], Service['ido2db-pgsql'] ]
  }

  pgsql::database::populate { 'icinga':
    username => 'icinga',
    password => 'icingaweb',
    schemafile => "/usr/local/src/icinga-pgsql/icinga-${icingaVersion}/module/idoutils/db/pgsql/pgsql.sql",
    require => Cmmi['icinga-pgsql'],
  }
}
