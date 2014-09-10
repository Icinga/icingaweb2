define parent_dirs {
  exec { "parent_dirs-${name}":
    command => "mkdir -p \"\$(dirname \"\$(readlink -m '${name}')\")\"",
    path    => '/bin:/usr/bin',
  }
}
