define icingaweb2::config::general (
  $source,
  $config    = hiera('icingaweb2::config'),
  $web_group = hiera('icingaweb2::group'),
  $replace   = true
) {
  include icingaweb2::config

  file { "${config}/${name}.ini":
     content => template("${source}/${name}.ini.erb"),
     owner   => 'root',
     group   => $web_group,
     mode    => 0660,
     replace => $replace,
  }
}
