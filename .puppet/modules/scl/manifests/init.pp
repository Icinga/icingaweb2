# Class: scl
#
#   This class installs centos-release-scl.
#
# Parameters:
#
# Actions:
#
# Requires:
#
# Sample Usage:
#
#   include scl
#
class scl {
  package { 'centos-release-scl':
    ensure => latest,
  }
}
