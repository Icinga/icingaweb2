class icinga-packages {
  yumrepo { 'icinga-packages':
    baseurl   => "http://packages.icinga.org/epel/6/snapshot/",
    enabled   => '1',
    gpgcheck  => '1',
    gpgkey    => 'http://packages.icinga.org/icinga.key',
    descr     => "Icinga Repository - ${::architecture}"
  }
}
