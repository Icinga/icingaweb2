define git_cmmi (
  $url,
  $configure='./configure',
  $make='make && make install'
) {
  include git

  exec { "git-clone-${name}":
    cwd     => '/usr/local/src',
    path    => '/usr/bin:/bin',
    command => "git clone '${url}' '${name}'",
    require => Class['git'],
  } -> cmmi_dir { $name:
    configure => $configure,
    make      => $make,
  }
}
