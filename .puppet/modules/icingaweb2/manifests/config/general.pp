define icingaweb2::config::general (
  $source,
  $config   = hiera('icingaweb2::config'),
  $replace  = true
) {
  include icingaweb2::config

  file { "${config}/${name}.ini":
     content => template("${source}/${name}.ini.erb"),
     owner   => 'root',
     group   => 'icingaweb',
     mode    => 0660,
     replace => $replace,
  }
}
