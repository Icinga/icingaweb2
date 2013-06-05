# Class: php
#
#   This class installs php.
#
# Parameters:
#
# Actions:
#
# Requires:
#
# Sample Usage:
#
#   include php
#
class php {

  package { 'php':
    ensure  => installed
  }
}
