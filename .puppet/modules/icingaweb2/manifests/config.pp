class icingaweb2::config (
  $config    = hiera('icingaweb2::config'),
  $web_group = hiera('icingaweb2::group')
) {
  group { $web_group:
      ensure => present,
  }

  file { [ "${config}", "${config}/enabledModules", "${config}/modules", "${config}/preferences" ]:
    ensure  => directory,
    owner   => 'root',
    group   => $web_group,
    mode    => '2770',
  }
}
