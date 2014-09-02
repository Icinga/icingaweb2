class icinga_packages {
  yumrepo { 'icinga_packages':
    baseurl   => "http://packages.icinga.org/epel/6/snapshot/",
    enabled   => '1',
    gpgcheck  => '1',
    gpgkey    => 'http://packages.icinga.org/icinga.key',
    descr     => "Icinga Repository - ${::architecture}"
  }
}
