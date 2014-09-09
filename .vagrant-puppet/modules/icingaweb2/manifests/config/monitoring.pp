define icingaweb2::config::monitoring ($source, $replace = true) {
  icingaweb2::config { "modules/monitoring/${name}":
    source  => $source,
    replace => $replace,
  }
}
