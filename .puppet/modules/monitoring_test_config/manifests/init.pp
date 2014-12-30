class monitoring_test_config {
  package { [
    'perl',
    'perl-Module-Install',
    'perl-CPAN',
    'perl-File-Which',
    'perl-Time-HiRes'
  ]:
    ensure => latest,
  }
  -> git_cmmi { 'Monitoring-Generator-TestConfig':
    url       => 'https://github.com/sni/Monitoring-Generator-TestConfig.git',
    configure => 'perl Makefile.PL',
    make      => 'make && make test && make install',
  }
  -> exec { 'create_monitoring_test_config':
    path    => '/usr/local/bin:/usr/bin:/bin',
    command => 'install -o root -g root -d /usr/local/share/misc/ && \
create_monitoring_test_config.pl -l icinga /usr/local/share/misc/monitoring_test_config',
    creates => '/usr/local/share/misc/monitoring_test_config',
  }
  -> monitoring_test_config::populate_plugins { [
    'test_hostcheck.pl', 'test_servicecheck.pl'
  ]: }
}
