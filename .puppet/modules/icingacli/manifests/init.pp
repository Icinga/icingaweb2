# TODO(el): This module is not reuseable because it relies on vagrant paths
class icingacli {
  file { '/usr/local/bin/icingacli':
    ensure  => link,
    target  => '/vagrant/bin/icingacli',
  }

  file { '/etc/bash_completion.d/icingacli':
    ensure  => link,
    target  => '/vagrant/etc/bash_completion.d/icingacli',
  }
}
