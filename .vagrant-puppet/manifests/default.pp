include apache
include mysql
include pgsql

Exec { path => '/bin:/usr/bin:/sbin' }

exec { 'create-mysql-icinga-db':
  unless  => 'mysql -uicinga -picinga icinga',
  command => 'mysql -uroot -e "CREATE DATABASE icinga; \
              GRANT ALL ON icinga.* TO icinga@localhost \
              IDENTIFIED BY \'icinga\';"',
  require => Service['mysqld']
}

exec{ 'create-pgsql-icinga-db':
  unless  => 'sudo -u postgres psql -l 2> /dev/null | grep -s icinga',
  command => 'sudo -u postgres psql -c "CREATE ROLE icinga WITH LOGIN PASSWORD \'icinga\';" && sudo -u postgres createdb -O icinga -E UTF8 icinga && sudo -u postgres createlang plpgsql icinga',
  require => Service['postgresql']
}

$icinga_packages = [ 'gcc', 'glibc', 'glibc-common', 'gd', 'gd-devel',
  'libpng', 'libpng-devel', 'net-snmp', 'net-snmp-devel', 'net-snmp-utils',
  'libdbi', 'libdbi-devel', 'libdbi-drivers',
  'libdbi-dbd-mysql', 'libdbi-dbd-pgsql' ]
package { $icinga_packages: ensure => installed }

php::extension { ['php-mysql', 'php-pgsql']:
  require => [Class['mysql'], Class['pgsql']]
}

group { 'icinga-cmd':
  ensure => present
}

user { 'icinga':
  ensure     => present,
  groups     => 'icinga-cmd',
  managehome => false
}

user { 'apache':
  groups  => 'icinga-cmd',
  require => [Package["${apache::apache}"], Group['icinga-cmd']]
}

cmmi { 'icinga-mysql':
  url     => 'http://sourceforge.net/projects/icinga/files/icinga/1.9.1/icinga-1.9.1.tar.gz/download',
  output  => 'icinga-1.9.1.tar.gz',
  flags   => '--prefix=/usr/local/icinga-mysql --with-command-group=icinga-cmd \
              --enable-idoutils --with-init-dir=/tmp/icinga-mysql/etc/init.d \
              --with-htmurl=/icinga-mysql --with-httpd-conf-file=/etc/httpd/conf.d/icinga-mysql.conf \
              --with-cgiurl=/icinga-mysql/cgi-bin \
              --with-http-auth-file=/usr/share/icinga/htpasswd.users \
              --with-plugin-dir=/usr/lib64/nagios/plugins/libexec',
  creates => '/usr/local/icinga-mysql',
  make    => 'make all && make fullinstall install-config',
  require => [User['icinga'], Cmmi['icinga-plugins']],
  notify  => Service["${apache::apache}"]
}

file { '/etc/init.d/icinga-mysql':
  source  => '/tmp/icinga-mysql/etc/init.d/icinga',
  require => Cmmi['icinga-mysql']
}

file { '/etc/init.d/ido2db-mysql':
  source  => '/tmp/icinga-mysql/etc/init.d/ido2db',
  require => Cmmi['icinga-mysql']
}

cmmi { 'icinga-pgsql':
  url     => 'http://sourceforge.net/projects/icinga/files/icinga/1.9.1/icinga-1.9.1.tar.gz/download',
  output  => 'icinga-1.9.1.tar.gz',
  flags   => '--prefix=/usr/local/icinga-pgsql \
              --with-command-group=icinga-cmd --enable-idoutils \
              --with-init-dir=/tmp/icinga-pgsql/etc/init.d \
              --with-htmurl=/icinga-pgsql --with-httpd-conf-file=/etc/httpd/conf.d/icinga-pgsql.conf \
              --with-cgiurl=/icinga-pgsql/cgi-bin \
              --with-http-auth-file=/usr/share/icinga/htpasswd.users \
              --with-plugin-dir=/usr/lib64/nagios/plugins/libexec',
  creates => '/usr/local/icinga-pgsql',
  make    => 'make all && make fullinstall install-config',
  require => [User['icinga'], Cmmi['icinga-plugins']],
  notify  => Service["${apache::apache}"]
}

file { '/etc/init.d/icinga-pgsql':
  source  => '/tmp/icinga-pgsql/etc/init.d/icinga',
  require => Cmmi['icinga-pgsql']
}

file { '/etc/init.d/ido2db-pgsql':
  source  => '/tmp/icinga-pgsql/etc/init.d/ido2db',
  require => Cmmi['icinga-pgsql']
}

exec { 'populate-icinga-mysql-db':
  unless  => 'mysql -uicinga -picinga icinga -e "SELECT * FROM icinga_dbversion;" &> /dev/null',
  command => 'mysql -uicinga -picinga icinga < /usr/local/src/icinga-mysql/icinga-1.9.1/module/idoutils/db/mysql/mysql.sql',
  require => [Cmmi['icinga-mysql'], Exec['create-mysql-icinga-db']]
}

exec { 'populate-icinga-pgsql-db':
  unless  => 'psql -U icinga -d icinga -c "SELECT * FROM icinga_dbversion;" &> /dev/null',
  command => 'sudo -u postgres psql -U icinga -d icinga < /usr/local/src/icinga-pgsql/icinga-1.9.1/module/idoutils/db/pgsql/pgsql.sql',
  require => [Cmmi['icinga-pgsql'], Exec['create-pgsql-icinga-db']]
}

service { 'icinga-mysql':
  ensure  => running,
  require => Cmmi['icinga-mysql']
}

service { 'ido2db-mysql':
  ensure  => running,
  require => Cmmi['icinga-mysql']
}

file { '/usr/local/icinga-mysql/etc/ido2db.cfg':
  content => template('icinga/ido2db-mysql.cfg.erb'),
  owner   => 'icinga',
  group   => 'icinga',
  require => Cmmi['icinga-mysql'],
  notify  => [Service['icinga-mysql'], Service['ido2db-mysql']]
}

file { '/usr/local/icinga-mysql/etc/idomod.cfg':
  source  => '/usr/local/icinga-mysql/etc/idomod.cfg-sample',
  owner   => 'icinga',
  group   => 'icinga',
  require => Cmmi['icinga-mysql'],
  notify  => [Service['icinga-mysql'], Service['ido2db-mysql']]
}

file { '/usr/local/icinga-mysql/etc/modules/idoutils.cfg':
  source  => '/usr/local/icinga-mysql/etc/modules/idoutils.cfg-sample',
  owner   => 'icinga',
  group   => 'icinga',
  require => Cmmi['icinga-mysql'],
  notify  => [Service['icinga-mysql'], Service['ido2db-mysql']]
}

service { 'icinga-pgsql':
  ensure  => running,
  require => Cmmi['icinga-pgsql']
}

service { 'ido2db-pgsql':
  ensure  => running,
  require => Cmmi['icinga-pgsql']
}

file { '/usr/local/icinga-pgsql/etc/ido2db.cfg':
  content => template('icinga/ido2db-pgsql.cfg.erb'),
  owner   => 'icinga',
  group   => 'icinga',
  require => Cmmi['icinga-pgsql'],
  notify  => [Service['icinga-pgsql'], Service['ido2db-pgsql']]
}

file { '/usr/local/icinga-pgsql/etc/idomod.cfg':
  source  => '/usr/local/icinga-pgsql/etc/idomod.cfg-sample',
  owner   => 'icinga',
  group   => 'icinga',
  require => Cmmi['icinga-pgsql'],
  notify  => [Service['icinga-pgsql'], Service['ido2db-pgsql']]
}

file { '/usr/local/icinga-pgsql/etc/modules/idoutils.cfg':
  source  => '/usr/local/icinga-pgsql/etc/modules/idoutils.cfg-sample',
  owner   => 'icinga',
  group   => 'icinga',
  require => Cmmi['icinga-pgsql'],
  notify  => [Service['icinga-pgsql'], Service['ido2db-pgsql']]
}

exec { 'iptables-allow-http':
  unless  => 'grep -Fxqe "-A INPUT -p tcp -m state --state NEW -m tcp --dport 80 -j ACCEPT" /etc/sysconfig/iptables',
  command => 'iptables -I INPUT 5 -p tcp -m state --state NEW -m tcp --dport 80 -j ACCEPT && iptables-save > /etc/sysconfig/iptables'
}

exec { 'icinga-htpasswd':
  creates => '/usr/share/icinga/htpasswd.users',
  command => 'mkdir /usr/share/icinga && htpasswd -b -c /usr/share/icinga/htpasswd.users icingaadmin icinga',
  require => Package["${apache::apache}"]
}

cmmi { 'icinga-plugins':
  url     => 'http://sourceforge.net/projects/nagiosplug/files/nagiosplug/1.4.16/nagios-plugins-1.4.16.tar.gz/download',
  output  => 'nagios-plugins-1.4.16.tar.gz',
  flags   => '--prefix=/usr/lib64/nagios/plugins \
              --with-nagios-user=icinga --with-nagios-group=icinga \
              --with-cgiurl=/icinga-mysql/cgi-bin',
  creates => '/usr/lib64/nagios/plugins/libexec',
  make    => 'make && make install',
  require => User['icinga']
}
