# Class: casperjs
#
# This module downloads, extracts, and installs casperjs tar.gz archives
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
#   class {'casperjs':
#     url     => 'https://github.com/n1k0/casperjs/tarball/1.0.2',
#     output  => 'casperjs-1.0.2.tar.gz',
#     creates => '/usr/local/casperjs'
#   }
#
class casperjs(
  $url,
  $output,
  $creates
) {

  Exec { path => '/usr/bin:/bin' }

  $cwd = '/usr/local/src'

  include wget

  exec { 'download-casperjs':
    cwd     => $cwd,
    command => "wget -q ${url} -O ${output}",
    creates => "${cwd}/${output}",
    timeout => 120,
    require => Class['wget']
  }

  $tld = inline_template('<%= File.basename(@output, ".tar.bz2") %>')
  $src = "${cwd}/casperjs"

  exec { 'extract-casperjs':
    cwd     => $cwd,
    command => "mkdir -p casperjs && tar --no-same-owner \
                --no-same-permissions -xzf ${output} -C ${src} \
                --strip-components 1",
    creates => $src,
    require => Exec['download-casperjs']
  }

  file { 'install-casperjs':
    path    => $creates,
    source  => $src,
    recurse => true,
    require => Exec['extract-casperjs']
  }

  file { 'link-casperjs-bin':
    ensure => "${creates}/bin/casperjs",
    path   => '/usr/local/bin/casperjs'
  }
}
