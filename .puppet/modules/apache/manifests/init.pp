# Class: apache
#
#   This class installs the apache server.
#
# Parameters:
#
# Actions:
#
# Requires:
#
# Sample Usage:
#
#   include apache
#
class apache {
  $apache = $::operatingsystem ? {
    /(Debian|Ubuntu)/ => 'apache2',
    /(RedHat|CentOS|Fedora)/ => 'httpd'
  }

  $user = $::operatingsystem ? {
    /(Debian|Ubuntu)/ => 'www-data',
    /(RedHat|CentOS|Fedora)/ => 'apache'
  }

  package { $apache:
    ensure => latest,
    alias  => 'apache',
  }

  service { $apache:
    ensure  => running,
    enable  => true,
    alias   => 'apache',
    require => Package['apache'],
  }

  @user { $user:
    alias => 'apache',
  }

  User <| alias == apache |>
}
