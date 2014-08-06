# Define: configure
#
# Run a gnu configure to prepare software for environment
#
# Parameters:
#   [*flags*]         - configure options.
#   [*path*]          - Target and working dir
#
define configure(
    $path,
    $flags
) {
  exec { "configure-${name}":
    cwd     => $path,
    command => "sh ./configure ${flags}",
  }
}
