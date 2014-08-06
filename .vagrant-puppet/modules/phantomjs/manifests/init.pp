# Class: phantomjs
#
# This module downloads, extracts, and installs phantomjs tar.bz2 archives
# using wget and tar.
#
# Parameters:
#   [*url*]         - fetch archive via wget from this url.
#   [*output*]      - filename to fetch the archive into.
#   [*creates*]     - target directory the software will install to.
#
# Actions:
#
# Requires:
#
# Sample Usage:
#
#   class {'phantomjs':
#     url     => 'https://phantomjs.googlecode.com/files/phantomjs-1.9.1-linux-x86_64.tar.bz2',
#     output  => 'phantomjs-1.9.1-linux-x86_64.tar.bz2',
#     creates => '/usr/local/phantomjs',
#   }
#
class phantomjs(
  $url,
  $output,
  $creates
) {

  Exec { path => '/usr/bin:/bin' }

  $cwd = '/usr/local/src'

  include wget

  exec { 'download-phantomjs':
    cwd     => $cwd,
    command => "wget -q ${url} -O ${output}",
    creates => "${cwd}/${output}",
    timeout => 120,
    require => Class['wget'],
  }

  $src = "${cwd}/phantomjs"

  exec { 'extract-phantomjs':
    cwd     => $cwd,
    command => "mkdir -p phantomjs && tar --no-same-owner \
                --no-same-permissions -xjf ${output} -C ${src} \
                --strip-components 1",
    creates => $src,
    require => Exec['download-phantomjs'],
  }

  file { 'install-phantomjs':
    path    => $creates,
    source  => $src,
    recurse => true,
    require => Exec['extract-phantomjs'],
  }

  file { 'link-phantomjs-bin':
    ensure => "${creates}/bin/phantomjs",
    path   => '/usr/local/bin/phantomjs',
  }
}
