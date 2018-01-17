# define: php::phpd
#
#   Provision php.d config
#
# Parameters:
#
# Actions:
#
# Requires:
#
# Sample Usage:
#
define php::phpd {

  include php

  file { "/etc/opt/rh/rh-php71/php.d/$name.ini":
    content => template("php/$name.ini.erb"),
    notify  => Service['apache'],
    require => Package['rh-php71-php-fpm'],
  }
}
