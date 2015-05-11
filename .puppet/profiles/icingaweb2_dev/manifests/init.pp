class icingaweb2_dev (
  $config    = hiera('icingaweb2::config'),
  $log       = hiera('icingaweb2::log'),
  $web_path  = hiera('icingaweb2::web_path'),
  $db_user   = hiera('icingaweb2::db_user'),
  $db_pass   = hiera('icingaweb2::db_pass'),
  $db_name   = hiera('icingaweb2::db_name'),
  $web_group = hiera('icingaweb2::group'),
) {
  include apache
  include php
  include php_imagick
  include icingaweb2::config
  include icingacli
  include icinga_packages
  include openldap

  # TODO(el): Only include zend_framework. Apache does not have to be notified
  class { 'zend_framework':
    notify => Service['apache'],
  }

  # TODO(el): icinga-gui is not a icingaweb2_dev package
  package { [ 'php-gd', 'php-intl', 'php-pdo', 'php-ldap', 'php-phpunit-PHPUnit', 'icinga-gui' ]:
    ensure => latest,
    notify => Service['apache'],
    require => Class['icinga_packages'],
  }

  Exec { path => '/usr/local/bin:/usr/bin:/bin' }

  # TODO(el): Enabling/disabling modules should be a resource
  User <| alias == apache |> { groups +> $web_group }
  -> exec { 'enable-monitoring-module':
    command => 'icingacli module enable monitoring',
    user    => 'apache',
    require => Class[[ 'icingacli', 'apache' ]],
  }
  -> exec { 'enable-test-module':
    command => 'icingacli module enable test',
    user    => 'apache'
  }

  # TODO(el): 'icingacmd' is NOT a icingaweb2_dev group
  group { 'icingacmd':
    ensure => present,
  }

  User <| alias == apache |> { groups +> 'icingacmd' }

  $log_dir = inline_template('<%= File.dirname(@log) %>')
  file { $log_dir:
    ensure  => directory,
    owner   => 'root',
    group   => $web_group,
    mode    => '2775'
  }

  $icingaadminSelect = "as CNT from icingaweb_user where name = \'icingaadmin\'\" |grep -qwe \'cnt=0\'"
  $icingaadminInsert = "\"INSERT INTO icingaweb_user (name, active, password_hash) VALUES (\'icingaadmin\', 1, \'\\\$1\\\$JMdnEc9M\\\$FW7yapAjv0atS43NkapGo/\');\""

  mysql::database::populate { "${db_name}":
    username   => "${db_user}",
    password   => "${db_pass}",
    privileges => 'ALL',
    schemafile => '/vagrant/etc/schema/mysql.schema.sql',
  }
  -> exec { 'mysql-icingaadmin':
    onlyif  => "mysql -u${db_user} -p${db_pass} ${db_name} -e \"select CONCAT(\'cnt=\', COUNT(name)) ${icingaadminSelect}",
    command => "mysql -u${db_user} -p${db_pass} ${db_name} -e ${icingaadminInsert}",
  }

  pgsql::database::populate { "${db_name}":
    username   => "${db_user}",
    password   => "${db_pass}",
    schemafile => '/vagrant/etc/schema/pgsql.schema.sql',
  }
  -> exec { 'pgsql-icingaadmin':
    onlyif      => "psql -U ${db_user} -w -d ${db_name} -c \"select 'cnt=' || COUNT(name) ${icingaadminSelect}",
    command     => "psql -U ${db_user} -w -d ${db_name} -c ${icingaadminInsert}",
    environment => "PGPASSWORD=${db_pass}",
  }

  file { '/etc/httpd/conf.d/icingaweb.conf':
    content   => template("$name/icingaweb.conf.erb"),
    notify    => Service['apache'],
  }

  icingaweb2::config::general { 'authentication':
    source => $name,
  }

  icingaweb2::config::general { [ 'config', 'resources', 'roles' ]:
    source  => $name,
    replace => false,
  }

  icingaweb2::config::module { [ 'backends', 'config', 'instances' ]:
    module  => 'monitoring',
    source  => 'puppet:///modules/icingaweb2_dev',
  }

  # TODO(el): Should be a resource
  package { 'iptables':
    ensure => latest
  }
  -> exec { 'iptables-allow-http':
    unless  => 'grep -qe "-A INPUT -p tcp -m state --state NEW -m tcp --dport 80 -j ACCEPT" /etc/sysconfig/iptables',
    command => '/sbin/iptables -I INPUT 1 -p tcp -m state --state NEW -m tcp --dport 80 -j ACCEPT && /sbin/iptables-save > /etc/sysconfig/iptables'
  }

  # TODO(el): Don't define inside a class
  define openldap_file {
    file { "openldap/${name}.ldif":
      path    => "/usr/share/openldap-servers/${name}.ldif",
      source  => "puppet:///modules/icingaweb2_dev/openldap/${name}.ldif",
      require => Class['openldap'],
    }
  }

  openldap_file { [ 'db', 'dit', 'users' ]: }

  exec { 'populate-openldap':
    # TODO(el): Split the command and use unless instead of trying to populate openldap everytime
    command => 'sudo ldapadd -c -Y EXTERNAL -H ldapi:/// -f /usr/share/openldap-servers/db.ldif || true && \
                sudo ldapadd -c -D cn=admin,dc=icinga,dc=org -x -w admin -f /usr/share/openldap-servers/dit.ldif || true && \
                sudo ldapadd -c -D cn=admin,dc=icinga,dc=org -x -w admin -f /usr/share/openldap-servers/users.ldif || true',
    require => [
      Service['slapd'],
      File[[
        'openldap/db.ldif',
        'openldap/dit.ldif',
        'openldap/users.ldif'
      ]]
    ],
  }

  # TODO(el): Should be a module
  package { 'php-deepend-Mockery':
    ensure => latest,
  }
}
