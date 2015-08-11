# Class: icinga_packages
#
#   This class adds the YUM repository for the Icinga packages.
#
# Sample Usage:
#
#   include icinga_packages
#
class icinga_packages {
  yumrepo { 'icinga_packages':
    baseurl   => "http://packages.icinga.org/epel/${::operatingsystemmajrelease}/snapshot/",
    enabled   => '1',
    gpgcheck  => '1',
    gpgkey    => 'http://packages.icinga.org/icinga.key',
    descr     => "Icinga Repository - ${::architecture}"
  }
}
