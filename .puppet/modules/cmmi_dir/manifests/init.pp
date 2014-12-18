define cmmi_dir (
  $configure='./configure',
  $make='make && make install'
) {
  Exec {
    path => '/usr/bin:/bin',
    cwd  => "/usr/local/src/${name}",
  }

  exec { "configure-${name}":
    command => $configure,
  } -> exec { "make-${name}":
    command => $make,
  }
}
