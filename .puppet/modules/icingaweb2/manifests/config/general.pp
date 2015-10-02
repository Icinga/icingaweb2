define icingaweb2::config::general (
  $source,
  $config    = hiera('icingaweb2::config'),
  $log       = hiera('icingaweb2::log'),
  $web_path  = hiera('icingaweb2::web_path'),
  $db_user   = hiera('icingaweb2::db_user'),
  $db_pass   = hiera('icingaweb2::db_pass'),
  $db_name   = hiera('icingaweb2::db_name'),
  $web_group = hiera('icingaweb2::group'),
  $replace   = true
) {
  include icingaweb2::config

  file { "${config}/${name}.ini":
     content => template("${source}/${name}.ini.erb"),
     owner   => 'root',
     group   => $web_group,
     mode    => '0660',
     replace => $replace,
  }
}
