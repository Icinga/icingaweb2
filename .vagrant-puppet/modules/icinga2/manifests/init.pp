class icinga2 {
  include icinga-packages

  service { 'icinga2':
    ensure  => running,
    enable  => true,
    require => Package['icinga2']
  }

  package { 'icinga2':
    ensure => latest,
    require => Class['icinga-packages'],
  }

  package { 'icinga2-doc':
    ensure => latest,
    require => Class['icinga-packages'],
  }
}
