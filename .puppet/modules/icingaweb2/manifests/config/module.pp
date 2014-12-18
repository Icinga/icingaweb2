define icingaweb2::config::module (
  $module,
  $source,
  $config  = hiera('icingaweb2::config'),
  $replace = true
) {
  include icingaweb2::config

  if ! defined(File["${config}/modules/${module}"]) {
    file { "${config}/modules/${module}":
      ensure  => directory,
      owner   => 'root',
      group   => 'icingaweb',
      mode    => '2770',
    }
  }

  file { "${config}/modules/${module}/${name}.ini":
     source  => "${source}/modules/${module}/${name}.ini",
     owner   => 'root',
     group   => 'icingaweb',
     mode    => 0660,
     replace => $replace,
  }
}
