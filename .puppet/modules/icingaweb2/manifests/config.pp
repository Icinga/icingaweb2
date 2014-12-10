class icingaweb2::config (
  $config = hiera('icingaweb2::config')
) {
  group { 'icingaweb':
      ensure => present,
  }

  file { [ "${config}", "${config}/enabledModules", "${config}/modules" ]:
    ensure  => directory,
    owner   => 'root',
    group   => 'icingaweb',
    mode    => '2770',
  }
}
