# Class: tar
#
#   This class installs tar.
#
# Sample Usage:
#
#   include tar
#
class tar {
  package { 'tar':
    ensure => installed,
  }
}
