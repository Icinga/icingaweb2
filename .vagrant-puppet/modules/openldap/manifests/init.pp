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
    require => Package['openldap-servers']
  }
}
