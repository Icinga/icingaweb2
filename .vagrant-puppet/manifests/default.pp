include apache
include mysql
include pgsql
include openldap

Exec { path => '/bin:/usr/bin:/sbin:/usr/sbin' }

$icingaVersion = '1.11.5'
$icinga2Version = '2.0.1'
$pluginVersion = '2.0'
$livestatusVersion = '1.2.4p5'
$phantomjsVersion = '1.9.1'
$casperjsVersion = '1.0.2'

exec { 'create-mysql-icinga-db':
  unless  => 'mysql -uicinga -picinga icinga',
  command => 'mysql -uroot -e "CREATE DATABASE icinga; \
              GRANT SELECT,INSERT,UPDATE,DELETE ON icinga.* TO icinga@localhost \
              IDENTIFIED BY \'icinga\';"',
  require => Service['mysqld']
}

exec { 'create-mysql-icinga2-db':
  unless  => 'mysql -uicinga2 -picinga2 icinga2',
  command => 'mysql -uroot -e "CREATE DATABASE icinga2; \
              GRANT SELECT,INSERT,UPDATE,DELETE ON icinga2.* to icinga2@localhost \
              IDENTIFIED BY \'icinga2\';"',
  require => Service['mysqld']
}

exec{ 'create-pgsql-icinga-db':
  unless  => 'sudo -u postgres psql -tAc "SELECT 1 FROM pg_roles WHERE rolname=\'icinga\'" | grep -q 1',
  command => 'sudo -u postgres psql -c "CREATE ROLE icinga WITH LOGIN PASSWORD \'icingaweb\';" && \
              sudo -u postgres createdb -O icinga -E UTF8 -T template0 icinga && \
              sudo -u postgres createlang plpgsql icinga',
  require => Service['postgresql']
}

$icinga_packages = [ 'gcc', 'glibc', 'glibc-common', 'gd', 'gd-devel',
  'libpng', 'libpng-devel', 'net-snmp', 'net-snmp-devel', 'net-snmp-utils',
  'libdbi', 'libdbi-devel', 'libdbi-drivers',
  'libdbi-dbd-mysql', 'libdbi-dbd-pgsql' ]
package { $icinga_packages: ensure => installed }

php::extension { ['php-mysql', 'php-pgsql', 'php-ldap']:
  require => [ Class['mysql'], Class['pgsql'], Class['openldap'] ]
}

php::extension { 'php-gd': }

group { 'icinga-cmd':
  ensure => present
}

group { 'icingacmd':
  ensure  => present,
  require => Package['icinga2']
}

user { 'icinga':
  ensure     => present,
  groups     => 'icinga-cmd',
  managehome => false
}

user { 'apache':
  groups  => ['icinga-cmd', 'vagrant', 'icingacmd'],
  require => [ Class['apache'], Group['icinga-cmd'], Group['icingacmd'] ]
}

cmmi { 'icinga-mysql':
  url     => "https://github.com/Icinga/icinga-core/releases/download/v${icingaVersion}/icinga-${icingaVersion}.tar.gz",
  output  => "icinga-${icingaVersion}.tar.gz",
  flags   => '--prefix=/usr/local/icinga-mysql --with-command-group=icinga-cmd \
              --enable-idoutils --with-init-dir=/usr/local/icinga-mysql/etc/init.d \
              --with-htmurl=/icinga-mysql --with-httpd-conf-file=/etc/httpd/conf.d/icinga-mysql.conf \
              --with-cgiurl=/icinga-mysql/cgi-bin \
              --with-http-auth-file=/usr/share/icinga/htpasswd.users \
              --with-plugin-dir=/usr/lib64/nagios/plugins',
  creates => '/usr/local/icinga-mysql',
  make    => 'make all && make fullinstall install-config',
  require => [ User['icinga'], Class['monitoring-plugins'], Package['apache'] ],
  notify  => Service['apache']
}

file { '/etc/init.d/icinga-mysql':
  source  => '/usr/local/icinga-mysql/etc/init.d/icinga',
  require => Cmmi['icinga-mysql']
}

file { '/etc/init.d/ido2db-mysql':
  source  => '/usr/local/icinga-mysql/etc/init.d/ido2db',
  require => Cmmi['icinga-mysql']
}

cmmi { 'icinga-pgsql':
  url     => "https://github.com/Icinga/icinga-core/releases/download/v${icingaVersion}/icinga-${icingaVersion}.tar.gz",
  output  => "icinga-${icingaVersion}.tar.gz",
  flags   => '--prefix=/usr/local/icinga-pgsql \
              --with-command-group=icinga-cmd --enable-idoutils \
              --with-init-dir=/usr/local/icinga-pgsql/etc/init.d \
              --with-htmurl=/icinga-pgsql --with-httpd-conf-file=/etc/httpd/conf.d/icinga-pgsql.conf \
              --with-cgiurl=/icinga-pgsql/cgi-bin \
              --with-http-auth-file=/usr/share/icinga/htpasswd.users \
              --with-plugin-dir=/usr/lib64/nagios/plugins',
  creates => '/usr/local/icinga-pgsql',
  make    => 'make all && make fullinstall install-config',
  require => [ User['icinga'], Class['monitoring-plugins'], Package['apache'] ],
  notify  => Service['apache']
}

file { '/etc/init.d/icinga-pgsql':
  source  => '/usr/local/icinga-pgsql/etc/init.d/icinga',
  require => Cmmi['icinga-pgsql']
}

file { '/etc/init.d/ido2db-pgsql':
  source  => '/usr/local/icinga-pgsql/etc/init.d/ido2db',
  require => Cmmi['icinga-pgsql']
}

exec { 'populate-icinga-mysql-db':
  unless  => 'mysql -uicinga -picinga icinga -e "SELECT * FROM icinga_dbversion;" &> /dev/null',
  command => "mysql -uroot icinga < /usr/local/src/icinga-mysql/icinga-${icingaVersion}/module/idoutils/db/mysql/mysql.sql",
  require => [ Cmmi['icinga-mysql'], Exec['create-mysql-icinga-db'] ]
}

exec { 'populate-icinga-pgsql-db':
  unless  => 'psql -U icinga -d icinga -c "SELECT * FROM icinga_dbversion;" &> /dev/null',
  command => "sudo -u postgres psql -U icinga -d icinga < /usr/local/src/icinga-pgsql/icinga-${icingaVersion}/module/idoutils/db/pgsql/pgsql.sql",
  require => [ Cmmi['icinga-pgsql'], Exec['create-pgsql-icinga-db'] ]
}

service { 'icinga-mysql':
  ensure  => running,
  require => File['/etc/init.d/icinga-mysql']
}

service { 'ido2db-mysql':
  ensure  => running,
  require => File['/etc/init.d/ido2db-mysql']
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

exec { 'iptables-allow-http':
  unless  => 'grep -Fxqe "-A INPUT -p tcp -m state --state NEW -m tcp --dport 80 -j ACCEPT" /etc/sysconfig/iptables',
  command => 'iptables -I INPUT 5 -p tcp -m state --state NEW -m tcp --dport 80 -j ACCEPT && iptables-save > /etc/sysconfig/iptables'
}

exec { 'icinga-htpasswd':
  creates => '/usr/share/icinga/htpasswd.users',
  command => 'mkdir -p /usr/share/icinga && htpasswd -b -c /usr/share/icinga/htpasswd.users icingaadmin icinga',
  require => Class['apache']
}

include monitoring-plugins

cmmi { 'mk-livestatus':
  url     => "http://mathias-kettner.de/download/mk-livestatus-${livestatusVersion}.tar.gz",
  output  => "mk-livestatus-${livestatusVersion}.tar.gz",
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
  notify  => [ Service['icinga-mysql'], Service['ido2db-mysql'] ]
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
  require => [ Service['slapd'], File['openldap/db.ldif'],
               File['openldap/dit.ldif'], File['openldap/users.ldif'] ]
}

class { 'phantomjs':
  url     => "https://phantomjs.googlecode.com/files/phantomjs-${phantomjsVersion}-linux-x86_64.tar.bz2",
  output  => "phantomjs-${phantomjsVersion}-linux-x86_64.tar.bz2",
  creates => '/usr/local/phantomjs'
}

class { 'casperjs':
  url     => "https://github.com/n1k0/casperjs/tarball/${casperjsVersion}",
  output  => "casperjs-${casperjsVersion}.tar.gz",
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
  creates => '/usr/lib/node_modules/mocha-cobertura-reporter',
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

exec { 'install npm/URIjs':
  command => 'npm install -g URIjs',
  creates => '/usr/lib/node_modules/URIjs',
  require => Exec['install nodejs']
}

exec { 'install php-ZendFramework':
  command => 'yum -d 0 -e 0 -y --enablerepo=epel install php-ZendFramework',
  unless  => 'rpm -qa | grep php-ZendFramework',
  require => Class['epel']
}

package { ['cmake', 'boost-devel', 'bison', 'flex']:
  ensure => installed
}

# icinga 2
define icinga2::feature ($feature = $title) {
  exec { "icinga2-feature-${feature}":
    path => '/bin:/usr/bin:/sbin:/usr/sbin',
    unless => "readlink /etc/icinga2/features-enabled/${feature}.conf",
    command => "icinga2-enable-feature ${feature}",
    require => [ Package['icinga2'] ],
    notify => Service['icinga2']
  }
}

yumrepo { 'icinga2-repo':
  baseurl   => "http://packages.icinga.org/epel/6/snapshot/",
  enabled   => '1',
  gpgcheck  => '1',
  gpgkey    => 'http://packages.icinga.org/icinga.key',
  descr     => "Icinga Repository - ${::architecture}"
}

exec { 'install nagios-plugins-all':
  command => 'yum -d 0 -e 0 -y --enablerepo=epel install nagios-plugins-all',
  unless  => 'rpm -qa | grep nagios-plugins-all',
  require => [ Class['epel'], Package['icinga2'] ],
}

package { 'icinga2':
  ensure => latest,
  require => Yumrepo['icinga2-repo'],
  alias => 'icinga2'
}

package { 'icinga2-bin':
  ensure => latest,
  require => [ Yumrepo['icinga2-repo'], Package['icinga2'] ],
  alias => 'icinga2-bin'
}

package { 'icinga2-doc':
  ensure => latest,
  require => Yumrepo['icinga2-repo'],
  alias => 'icinga2-doc'
}

# icinga 2 classic ui
package { 'icinga2-classicui-config':
  ensure => latest,
  before => Package["icinga-gui"],
  require => [ Yumrepo['icinga2-repo'], Package['icinga2'] ],
  notify => Service['apache']
}

package { 'icinga-gui':
  ensure => latest,
  require => Yumrepo['icinga2-repo'],
  alias => 'icinga-gui'
}

icinga2::feature { 'statusdata':
  require => Package['icinga2-classicui-config']
}

icinga2::feature { 'command':
  require => Package['icinga2-classicui-config']
}

icinga2::feature { 'compatlog':
  require => Package['icinga2-classicui-config']
}

# icinga 2 ido mysql
package { 'icinga2-ido-mysql':
  ensure => latest,
  require => Yumrepo['icinga2-repo'],
  alias => 'icinga2-ido-mysql'
}

exec { 'populate-icinga2-mysql-db':
  unless  => 'mysql -uicinga2 -picinga2 icinga2 -e "SELECT * FROM icinga_dbversion;" &> /dev/null',
  command => 'mysql -uroot icinga2 < /usr/share/icinga2-ido-mysql/schema/mysql.sql',
  require => [ Exec['create-mysql-icinga2-db'], Package['icinga2-ido-mysql'] ]
}

file { '/etc/icinga2/features-available/ido-mysql.conf':
  source  => 'puppet:////vagrant/.vagrant-puppet/files/etc/icinga2/features-available/ido-mysql.conf',
  owner   => 'icinga',
  group   => 'icinga',
  require => Package['icinga2'],
  notify => Service['icinga2']
}

file { '/etc/icinga2/features-enabled/ido-mysql.conf':
  ensure  => 'link',
  target  => '/etc/icinga2/features-available/ido-mysql.conf',
  owner   => 'root',
  group   => 'root',
  require => Package['icinga2-ido-mysql']
}

icinga2::feature { 'ido-mysql':
  require => Exec['populate-icinga2-mysql-db']
}


# icinga 2 test config
file { '/etc/icinga2/conf.d/test-config.conf':
  source  => 'puppet:////vagrant/.vagrant-puppet/files/etc/icinga2/conf.d/test-config.conf',
  owner   => 'icinga',
  group   => 'icinga',
  require => [ Package['icinga2'], Exec['create_monitoring_test_config'] ]
}

file { '/etc/icinga2/conf.d/commands.conf':
  source  => 'puppet:////vagrant/.vagrant-puppet/files/etc/icinga2/conf.d/commands.conf',
  owner   => 'icinga',
  group   => 'icinga',
  require => Package['icinga2']
}

file { '/etc/icinga2/constants.conf':
  source  => 'puppet:////vagrant/.vagrant-puppet/files/etc/icinga2/constants.conf',
  owner   => 'icinga',
  group   => 'icinga',
  require => Package['icinga2']
}

service { 'icinga2':
  ensure  => running,
  require => [
    Package['icinga2'],
    File['/etc/icinga2/features-enabled/ido-mysql.conf'],
    File['/etc/icinga2/conf.d/test-config.conf'],
    File['/etc/icinga2/conf.d/commands.conf']
  ]
}

exec { 'install php-ZendFramework-Db-Adapter-Pdo-Mysql':
  command => 'yum -d 0 -e 0 -y --enablerepo=epel install php-ZendFramework-Db-Adapter-Pdo-Mysql',
  unless  => 'rpm -qa | grep php-ZendFramework-Db-Adapter-Pdo-Mysql',
  require => Exec['install php-ZendFramework']
}

file { '/etc/motd':
  source => 'puppet:////vagrant/.vagrant-puppet/files/etc/motd',
  owner  => root,
  group  => root
}

user { 'vagrant':
  groups  => 'icinga-cmd',
  require => Group['icinga-cmd']
}

exec { 'create-mysql-icinga_unittest-db':
  unless  => 'mysql -uicinga_unittest -picinga_unittest icinga_unittest',
  command => 'mysql -uroot -e "CREATE DATABASE icinga_unittest; \
              GRANT ALL ON icinga_unittest.* TO icinga_unittest@localhost \
              IDENTIFIED BY \'icinga_unittest\';"',
  require => Service['mysqld']
}

exec{ 'create-pgsql-icinga_unittest-db':
  unless  => 'sudo -u postgres psql -tAc "SELECT 1 FROM pg_roles WHERE rolname=\'icinga_unittest\'" | grep -q 1',
  command => 'sudo -u postgres psql -c "CREATE ROLE icinga_unittest WITH LOGIN PASSWORD \'icinga_unittest\';" && \
              sudo -u postgres createdb -O icinga_unittest -E UTF8 -T template0 icinga_unittest && \
              sudo -u postgres createlang plpgsql icinga_unittest',
  require => Service['postgresql']
}

exec { 'install php-ZendFramework-Db-Adapter-Pdo-Pgsql':
  command => 'yum -d 0 -e 0 -y --enablerepo=epel install php-ZendFramework-Db-Adapter-Pdo-Pgsql',
  unless  => 'rpm -qa | grep php-ZendFramework-Db-Adapter-Pdo-Pgsql',
  require => Exec['install php-ZendFramework']
}


#
# Following section installs the Perl module Monitoring::Generator::TestConfig in order to create test config to
# */usr/local/share/misc/monitoring_test_config*. Then the config is copied to *<instance>/etc/conf.d/test_config/* of
# both the MySQL and PostgreSQL Icinga instance
#
cpan { 'Monitoring::Generator::TestConfig':
  creates => '/usr/local/share/perl5/Monitoring/Generator/TestConfig.pm',
  timeout => 600
}

exec { 'create_monitoring_test_config':
  command => 'sudo install -o root -g root -d /usr/local/share/misc/ && \
              sudo /usr/local/bin/create_monitoring_test_config.pl -l icinga \
              /usr/local/share/misc/monitoring_test_config',
  creates => '/usr/local/share/misc/monitoring_test_config',
  require => Cpan['Monitoring::Generator::TestConfig']
}

define populate_monitoring_test_config {
  file { "/usr/local/icinga-mysql/etc/conf.d/test_config/${name}.cfg":
    owner   => 'icinga',
    group   => 'icinga',
    source  => "/usr/local/share/misc/monitoring_test_config/etc/conf.d/${name}.cfg",
    notify  => Service['icinga-mysql']
  }
  file { "/usr/local/icinga-pgsql/etc/conf.d/test_config/${name}.cfg":
    owner   => 'icinga',
    group   => 'icinga',
    source  => "/usr/local/share/misc/monitoring_test_config/etc/conf.d/${name}.cfg",
    notify  => Service['icinga-pgsql']
  }
}

file { '/usr/local/icinga-mysql/etc/conf.d/test_config/':
  ensure  => directory,
  owner   => icinga,
  group   => icinga,
  require => Cmmi['icinga-mysql']
}

file { '/usr/local/icinga-pgsql/etc/conf.d/test_config/':
  ensure  => directory,
  owner   => icinga,
  group   => icinga,
  require => Cmmi['icinga-pgsql']
}

populate_monitoring_test_config { ['commands', 'contacts', 'dependencies',
                                   'hostgroups', 'hosts', 'servicegroups', 'services']:
  require   => [ Exec['create_monitoring_test_config'],
                 File['/usr/local/icinga-mysql/etc/conf.d/test_config/'],
                 File['/usr/local/icinga-pgsql/etc/conf.d/test_config/'] ]
}

define populate_monitoring_test_config_plugins {
  file { "/usr/lib64/nagios/plugins/${name}":
    owner   => 'icinga',
    group   => 'icinga',
    source  => "/usr/local/share/misc/monitoring_test_config/plugins/${name}",
    notify  => [ Service['icinga-mysql'], Service['icinga-pgsql'] ]
  }
}

populate_monitoring_test_config_plugins{ ['test_hostcheck.pl', 'test_servicecheck.pl']:
  require   => [ Exec['create_monitoring_test_config'],
                 Cmmi['icinga-mysql'],
                 Cmmi['icinga-pgsql'] ]
}

#
# Following section creates and populates MySQL and PostgreSQL Icinga Web 2 databases
#
exec { 'create-mysql-icingaweb-db':
  unless  => 'mysql -uicingaweb -picingaweb icingaweb',
  command => 'mysql -uroot -e "CREATE DATABASE icingaweb; \
              GRANT ALL ON icingaweb.* TO icingaweb@localhost \
              IDENTIFIED BY \'icingaweb\';"',
  require => Service['mysqld']
}

exec { 'create-pgsql-icingaweb-db':
  unless  => 'sudo -u postgres psql -tAc "SELECT 1 FROM pg_roles WHERE rolname=\'icingaweb\'" | grep -q 1',
  command => 'sudo -u postgres psql -c "CREATE ROLE icingaweb WITH LOGIN PASSWORD \'icinga\';" && \
              sudo -u postgres createdb -O icingaweb -E UTF8 -T template0 icingaweb && \
              sudo -u postgres createlang plpgsql icingaweb',
  require => Service['postgresql']
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

#
# Following section creates the Icinga command proxy to /usr/local/icinga-mysql/var/rw/icinga.cmd (which is the
# config's default path for the Icinga command pipe) in order to send commands to both the MySQL and PostgreSQL instance
#
file { [ '/usr/local/icinga/', '/usr/local/icinga/var/', '/usr/local/icinga/var/rw/' ]:
  ensure  => directory,
  owner   => icinga,
  group   => icinga,
  require => User['icinga']
}

file { '/usr/local/bin/icinga_command_proxy':
  source => 'puppet:////vagrant/.vagrant-puppet/files/usr/local/bin/icinga_command_proxy',
  owner  => root,
  group  => root,
  mode   => 755
}

file { '/etc/init.d/icinga_command_proxy':
  source  => 'puppet:////vagrant/.vagrant-puppet/files/etc/init.d/icinga_command_proxy',
  owner   => root,
  group   => root,
  mode    => 755,
  require => File['/usr/local/bin/icinga_command_proxy']
}

service { 'icinga_command_proxy':
  ensure  => running,
  require => [ File['/etc/init.d/icinga_command_proxy'], Service['icinga-mysql'], Service['icinga-pgsql'] ]
}

exec { 'create-mysql-icinga_web-db':
  unless  => 'mysql -uicinga_web -picinga_web icinga_web',
  command => 'mysql -uroot -e "CREATE DATABASE icinga_web; \
              GRANT ALL ON icinga_web.* TO icinga_web@localhost \
              IDENTIFIED BY \'icinga_web\';"',
  require => Service['mysqld']
}

cmmi { 'icinga-web':
  url     => 'http://sourceforge.net/projects/icinga/files/icinga-web/1.10.0-beta/icinga-web-1.10.0-beta.tar.gz/download',
  output  => 'icinga-web-1.10.0-beta.tar.gz',
  flags   => '--prefix=/usr/local/icinga-web',
  creates => '/usr/local/icinga-web',
  make    => 'make install && make install-apache-config',
  require => Service['icinga_command_proxy'],
  notify  => Service['apache']
}

exec { 'populate-icinga_web-mysql-db':
  unless  => 'mysql -uicinga_web -picinga_web icinga_web -e "SELECT * FROM nsm_user;" &> /dev/null',
  command => 'mysql -uicinga_web -picinga_web icinga_web < /usr/local/src/icinga-web/icinga-web-1.10.0-beta/etc/schema/mysql.sql',
  require => [ Exec['create-mysql-icinga_web-db'], Cmmi['icinga-web'] ]
}

file { '/var/www/html/icingaweb':
  ensure => absent,
}

file { '/etc/httpd/conf.d/icingaweb.conf':
  source    => 'puppet:////vagrant/.vagrant-puppet/files/etc/httpd/conf.d/icingaweb.conf',
  require   => Package['apache'],
  notify    => Service['apache']
}

file { '/etc/icingaweb':
  ensure    => 'directory',
  owner     => 'apache',
  group     => 'apache'
}

file { '/etc/icingaweb/authentication.ini':
  source    => 'puppet:////vagrant/.vagrant-puppet/files/etc/icingaweb/authentication.ini',
  owner     => 'apache',
  group     => 'apache',
  require   => File['/etc/icingaweb']
}

file { '/etc/icingaweb/config.ini':
  ensure    => file,
  owner     => 'apache',
  group     => 'apache',
}

file { '/etc/icingaweb/resources.ini':
  source    => 'puppet:////vagrant/.vagrant-puppet/files/etc/icingaweb/resources.ini',
  owner     => 'apache',
  group     => 'apache',
  replace   => false
}

file { ['/etc/icingaweb/enabledModules', '/etc/icingaweb/modules', '/etc/icingaweb/modules/monitoring']:
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
  source    => 'puppet:////vagrant/.vagrant-puppet/files/etc/icingaweb/modules/monitoring/instances.ini',
  owner     => 'apache',
  group     => 'apache',
}

# pear::package { 'deepend/Mockery':
#  channel => 'pear.survivethedeepend.com'
# }

# icingacli
file { '/usr/local/bin/icingacli':
  ensure  => 'link',
  target  => '/vagrant/bin/icingacli',
  owner   => 'apache',
  group   => 'apache',
  require => [ File['/etc/icingaweb'], File['/etc/bash_completion.d/icingacli'] ]
}

exec { 'install bash-completion':
  command => 'yum -d 0 -e 0 -y --enablerepo=epel install bash-completion',
  unless  => 'rpm -qa | grep bash-completion',
  require => Class['epel']
}

file { '/etc/bash_completion.d/icingacli':
   source    => 'puppet:////vagrant/etc/bash_completion.d/icingacli',
   owner     => 'root',
   group     => 'root',
   mode      => 755,
   require   => Exec['install bash-completion']
}
