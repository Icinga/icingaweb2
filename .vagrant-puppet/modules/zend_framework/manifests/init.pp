# Class: zend_framework
#
#   This class installs the Zend Framework.
#
# Requires:
#
#   epel
#
# Sample Usage:
#
#   include zend_framework
#
class zend_framework {
  include epel

  package { [
    'php-ZendFramework',
    'php-ZendFramework-Db-Adapter-Pdo-Mysql',
    'php-ZendFramework-Db-Adapter-Pdo-Pgsql'
  ]:
    ensure  => latest,
    require => Class['epel'],
  }
}
