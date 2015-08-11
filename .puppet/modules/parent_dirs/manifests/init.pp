# TODO(el): Remove this. It's always executed and hackish
define parent_dirs ($user = 'root') {
  exec { "parent_dirs-${name}":
    command => "mkdir -p \"\$(dirname \"\$(readlink -m '${name}')\")\"",
    path    => '/bin:/usr/bin',
    user    => $user,
  }
}
