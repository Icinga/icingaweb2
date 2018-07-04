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
  exec { 'rpm --import RPM-GPG-KEY-EPEL':
    command => '/bin/rpm --import https://dl.fedoraproject.org/pub/epel/RPM-GPG-KEY-EPEL-7',
  }
  -> exec { 'yum install epel-release-latest':
    command => '/bin/yum install -y https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm',
    creates => '/etc/yum.repos.d/epel.repo',
  }
}

