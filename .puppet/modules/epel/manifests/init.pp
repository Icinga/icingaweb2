# Class: epel
#
#   Configure EPEL repository.
#
# Parameters:
#
# Actions:
#
# Requires:
#
# Sample Usage:
#
#   include epel
#
class epel {

  yumrepo { 'epel':
    mirrorlist => "http://mirrors.fedoraproject.org/mirrorlist?repo=epel-${::operatingsystemmajrelease}&arch=${::architecture}",
    enabled    => '1',
    gpgcheck   => '0',
    descr      => "Extra Packages for Enterprise Linux ${::operatingsystemmajrelease} - ${::architecture}"
  }
}

