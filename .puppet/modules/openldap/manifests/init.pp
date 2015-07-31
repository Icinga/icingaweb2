# Class: openldap
#
#   This class installs the openldap servers and clients software.
#
# Parameters:
#
# Actions:
#
# Requires:
#
# Sample Usage:
#
#   include openldap
#
class openldap {

  package { ['openldap-servers', 'openldap-clients']:
    ensure => latest,
  }

  service { 'slapd':
    ensure  => running,
    require => Package['openldap-servers'],
  }

  if versioncmp($::operatingsystemmajrelease, '7') >= 0 {
    ['core', 'cosine', 'inetorgperson', 'nis', 'misc', 'openldap'].each |String $schema| {
      exec { "slapd-schema-${schema}":
        command => "ldapadd -Y EXTERNAL -H ldapi:// -f /etc/openldap/schema/${schema}.ldif",
        group   => 'root',
        require => Package['openldap-servers'],
        unless  => "test -n \"$(find /etc/openldap/slapd.d/cn=config/cn=schema/ -name cn={*}${schema}.ldif -print -quit)\"",
        user    => 'root',
      }
    }
  }
}
