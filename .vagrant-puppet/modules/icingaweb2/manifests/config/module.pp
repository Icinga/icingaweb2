define icingaweb2::config::module ($source, $module = 'monitoring', $replace = true) {
  icingaweb2::config::general { "modules/${module}/${name}":
    source  => $source,
    replace => $replace,
  }
}
