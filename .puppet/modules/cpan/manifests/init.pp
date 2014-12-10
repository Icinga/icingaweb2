# Define: cpan
#
# Download and install Perl modules from the Perl Archive Network, the canonical location for Perl code and modules.
#
# Parameters:
#   [*creates*]  - target directory the software will install to.
#   [*timeout* ] - timeout for the CPAN command.
#
# Actions:
#
# Requires:
#
#   perl
#
# Sample Usage:
#
#   cpan { 'perl-module':
#     creates => '/usr/local/share/perl5/perl-module',
#     timeout => 600
#   }
#
define cpan(
  $creates,
  $timeout = 0
) {
  include perl

  file { [ '/root/.cpan/', '/root/.cpan/CPAN/' ]:
    ensure  => directory
  }

  file { '/root/.cpan/CPAN/MyConfig.pm':
    content => template('cpan/MyConfig.pm.erb'),
    require => File[[ '/root/.cpan/', '/root/.cpan/CPAN/' ]],
  }

  exec { "cpan-${name}":
    command => "perl -MCPAN -e 'install ${name}'",
    creates => $creates,
    path    => '/usr/local/bin:/usr/bin',
    require => [
      Class['perl'],
      File['/root/.cpan/CPAN/MyConfig.pm']
    ],
    timeout => $timeout
  }
}
