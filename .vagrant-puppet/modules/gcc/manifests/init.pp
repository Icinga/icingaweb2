# Class: gcc
#
#   This class installs gcc.
#
# Sample Usage:
#
#   include gcc
#
class gcc {
  package { 'gcc':
    ensure => latest,
  }
}
