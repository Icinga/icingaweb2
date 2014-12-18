stage { 'repositories':
  before => Stage['main'],
}

node default {
  class { 'epel':
    stage => repositories,
  }
  include icinga2_dev
  include icingaweb2_dev
  include motd
  file { '/etc/profile.d/env.sh':
    source => 'puppet:////vagrant/.puppet/files/etc/profile.d/env.sh'
  }
}
