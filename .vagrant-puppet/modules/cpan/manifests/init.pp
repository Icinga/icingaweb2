# Define: cpan
#
# Download and install Perl modules from the Perl Archive Network, the gateway to all things Perl.
# The canonical location for Perl code and modules.
#
# Parameters:
#   [*creates*]  - target directory the software will install to.
#   [*timeout* ] - timeout for the CPAN command.
#
# Actions:
#
# Requires:
#
# Sample Usage:
#
#  cpan { 'perl-module':
#    creates => '/usr/local/share/perl5/perl-module',
#    timeout => 600
#  }
#
define cpan(
  $creates,
  $timeout
) {

  Exec { path => '/usr/bin' }

  package { 'perl-CPAN':
    ensure => installed
  }

  file { '/root/.cpan/CPAN/MyConfig.pm':
    content => template('cpan/MyConfig.pm.erb'),
    require => Package['perl-CPAN']
  }

  exec { "cpan-${name}":
    command => "sudo perl -MCPAN -e 'install ${name}'",
    creates => $creates,
    require => File['/root/.cpan/CPAN/MyConfig.pm'],
    timeout => $timeout
  }
}
