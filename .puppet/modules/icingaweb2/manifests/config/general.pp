define icingaweb2::config::general (
  $source,
  $config   = hiera('icingaweb2::config'),
  $replace  = true
) {
  include icingaweb2::config

  file { "${config}/${name}.ini":
     source  => "${source}/${name}.ini",
     owner   => 'root',
     group   => 'icingaweb',
     mode    => 0660,
     replace => $replace,
  }
}
