define icingaweb2::config::module ($source, $module = 'monitoring', $replace = true) {
  icingaweb2::config { "modules/${module}/${name}":
    source  => $source,
    replace => $replace,
  }
}
