# Class: perl
#
#   This class installs perl.
#
# Sample Usage:
#
#   include perl
#
class perl {
  $perl = 'perl-5.20.0'
  $perlDir = '/opt/perl'

  cmmi { $perl:
    url               => "http://www.cpan.org/src/5.0/${perl}.tar.gz",
    output            => "${perl}.tar.gz",
    creates           => $perlDir,
    configure_command => 'sh ./Configure',
    flags             => "-des -Dprefix=${perlDir}",
  }
}
