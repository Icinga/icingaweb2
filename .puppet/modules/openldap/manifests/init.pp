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

  package { [ 'openldap-servers', 'openldap-clients', ]:
    ensure => latest,
  }

  service { 'slapd':
    enable  => true,
    ensure  => running,
    require => Package['openldap-servers'],
  }

  if versioncmp($::operatingsystemmajrelease, '7') >= 0 {
    openldap::schema{ 'core': }
    -> openldap::schema{ 'cosine': }
    -> openldap::schema{ 'inetorgperson': }
    -> openldap::schema{ 'nis': }
    -> openldap::schema{ 'misc': }
    -> openldap::schema{ 'openldap': }
  }
}
