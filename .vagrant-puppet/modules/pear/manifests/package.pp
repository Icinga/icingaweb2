# Define: pear::package
#
#   Install additional PEAR packages
#
# Parameters:
#
# Actions:
#
# Requires:
#
#   pear
#
# Sample Usage:
#
#   pear::package { 'phpunit': }
#
define pear::package(
  $channel
) {

  Exec { path => '/usr/bin' }

  include pear

  if $::require {
    $require_ = [Class['pear'], $::require]
  } else {
    $require_ = Class['pear']
  }

  if $channel {
    exec { "pear discover ${channel}":
      command => "sudo pear channel-discover ${channel}",
      unless => "pear channel-info ${channel}",
      require => $require_,
      before => Exec["pear install ${name}"],
    }
  }

  exec { "pear install ${name}":
    command => "pear install --alldeps ${name}",
    unless => "pear list ${name}",
    require => $require_,
  }

  exec { "pear upgrade ${name}":
    command => "pear upgrade ${name}",
    require => Exec["pear install ${name}"],
  }
}
