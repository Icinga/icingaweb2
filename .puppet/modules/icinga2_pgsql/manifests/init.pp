class icinga2_pgsql {
  include icinga2
  include icinga_packages

  package { 'icinga2-ido-pgsql':
    ensure  => latest,
    require => Class['icinga_packages'],
  }
  -> pgsql::database::populate { 'icinga2':
    username   => 'icinga2',
    password   => 'icinga2',
    schemafile => '/usr/share/icinga2-ido-pgsql/schema/pgsql.sql',
  }
# Because Icinga 2 does not handle more than one IDO connection properly, The ido-pgsql will not be enabled by default.
#  -> icinga2::feature { 'ido-pgsql':
#    source => 'puppet:///modules/icinga2_pgsql',
#  }
}
