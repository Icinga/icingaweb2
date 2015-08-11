# Class: git
#
#   This class installs git.
#
# Sample Usage:
#
#   include git
#
class git {
  package { 'git':
    ensure => latest,
  }
}
