include apache
include mysql
include pgsql
include openldap

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

php::extension { ['php-mysql', 'php-pgsql', 'php-ldap']:
  require => [Class['mysql'], Class['pgsql'], Class['openldap']]
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

cmmi { 'mk-livestatus':
  url     => 'http://mathias-kettner.de/download/mk-livestatus-1.2.2p1.tar.gz',
  output  => 'mk-livestatus-1.2.2p1.tar.gz',
  flags   => '--prefix=/usr/local/icinga-mysql --exec-prefix=/usr/local/icinga-mysql',
  creates => '/usr/local/icinga-mysql/lib/mk-livestatus',
  make    => 'make && make install',
  require => Cmmi['icinga-mysql']
}

file { '/usr/local/icinga-mysql/etc/modules/mk-livestatus.cfg':
  content => template('mk-livestatus/mk-livestatus.cfg.erb'),
  owner   => 'icinga',
  group   => 'icinga',
  require => Cmmi['mk-livestatus'],
  notify  => [Service['icinga-mysql'], Service['ido2db-mysql']]
}

file { 'openldap/db.ldif':
  path    => '/usr/share/openldap-servers/db.ldif',
  source  => 'puppet:///modules/openldap/db.ldif',
  require => Class['openldap']
}

file { 'openldap/dit.ldif':
  path    => '/usr/share/openldap-servers/dit.ldif',
  source  => 'puppet:///modules/openldap/dit.ldif',
  require => Class['openldap']
}

file { 'openldap/users.ldif':
  path    => '/usr/share/openldap-servers/users.ldif',
  source  => 'puppet:///modules/openldap/users.ldif',
  require => Class['openldap']
}

exec { 'populate-openldap':
  # TODO: Split the command and use unless instead of trying to populate openldap everytime
  command => 'sudo ldapadd -c -Y EXTERNAL -H ldapi:/// -f /usr/share/openldap-servers/db.ldif || true && \
              sudo ldapadd -c -D cn=admin,dc=icinga,dc=org -x -w admin -f /usr/share/openldap-servers/dit.ldif || true && \
              sudo ldapadd -c -D cn=admin,dc=icinga,dc=org -x -w admin -f /usr/share/openldap-servers/users.ldif || true',
  require => [Service['slapd'], File['openldap/db.ldif'],
              File['openldap/dit.ldif'], File['openldap/users.ldif']]
}

class { 'phantomjs':
  url     => 'https://phantomjs.googlecode.com/files/phantomjs-1.9.1-linux-x86_64.tar.bz2',
  output  => 'phantomjs-1.9.1-linux-x86_64.tar.bz2',
  creates => '/usr/local/phantomjs'
}

class { 'casperjs':
  url     => 'https://github.com/n1k0/casperjs/tarball/1.0.2',
  output  => 'casperjs-1.0.2.tar.gz',
  creates => '/usr/local/casperjs'
}

file { '/etc/profile.d/env.sh':
  source => 'puppet:////vagrant/.vagrant-puppet/files/etc/profile.d/env.sh'
}

include epel

exec { 'install PHPUnit':
  command => 'yum -d 0 -e 0 -y --enablerepo=epel install php-phpunit-PHPUnit',
  unless  => 'rpm -qa | grep php-phpunit-PHPUnit',
  require => Class['epel']
}

exec { 'install PHP CodeSniffer':
  command => 'yum -d 0 -e 0 -y --enablerepo=epel install php-pear-PHP-CodeSniffer',
  unless  => 'rpm -qa | grep php-pear-PHP-CodeSniffer',
  require => Class['epel']
}

exec { 'install nodejs':
  command => 'yum -d 0 -e 0 -y --enablerepo=epel install npm',
  unless  => 'rpm -qa | grep ^npm',
  require => Class['epel']
}

exec { 'install npm/mocha':
  command => 'npm install -g mocha',
  creates => '/usr/lib/node_modules/mocha',
  require => Exec['install nodejs']
}

exec { 'install npm/mocha-cobertura-reporter':
  command => 'npm install -g mocha-cobertura-reporter',
  creates => '/usr/lib/node_modules/cobertura',
  require => Exec['install npm/mocha']
}

exec { 'install npm/jshint':
  command => 'npm install -g jshint',
  creates => '/usr/lib/node_modules/jshint',
  require => Exec['install nodejs']
}

exec { 'install npm/expect':
  command => 'npm install -g expect',
  creates => '/usr/lib/node_modules/expect',
  require => Exec['install nodejs']
}

exec { 'install npm/should':
  command => 'npm install -g should',
  creates => '/usr/lib/node_modules/should',
  require => Exec['install nodejs']
}

exec { 'install ZendFramework':
  command => 'yum -d 0 -e 0 -y --enablerepo=epel install php-ZendFramework',
  unless  => 'rpm -qa | grep php-ZendFramework',
  require => Class['epel']
}

package { 'boost-devel':
  ensure => installed
}

cmmi { 'icinga2':
  url     => 'http://sourceforge.net/projects/icinga/files/icinga2/0.0.1/icinga2-0.0.1.tar.gz/download',
  output  => 'icinga2-0.0.1.tar.gz',
  flags   => '--prefix=/usr/local/icinga2',
  creates => '/usr/local/icinga2',
  make    => 'make && make install',
  require => Package['boost-devel'],
  timeout => 600
}
