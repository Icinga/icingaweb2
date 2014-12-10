class monitoring_plugins {
  include epel

  # nagios plugins from epel
  package { 'nagios-plugins-all':
    ensure  => latest,
    require => Class['epel']
  }
}
