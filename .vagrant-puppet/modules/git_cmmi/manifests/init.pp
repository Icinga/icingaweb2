define git_cmmi (
  $url,
  $configure='./configure',
  $make='make && make install'
) {
  include git

  $srcDir = '/usr/local/src'

  exec { "git-clone-${name}":
    cwd     => $srcDir,
    path    => '/usr/bin:/bin',
    unless  => "test -d '${srcDir}/${name}/.git'",
    command => "git clone '${url}' '${name}'",
    require => Class['git'],
  } -> cmmi_dir { $name:
    configure => $configure,
    make      => $make,
  }
}
