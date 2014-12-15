# Define: mysql::database::create
#
#   Create a MySQL database
#
# Parameters:
#
#   [*username*]   - name of the user the database belongs to
#   [*password*]   - password of the user the database belongs to
#   [*privileges*] - privileges of the user the database belongs to
#
# Requires:
#
#   mysql
#
# Sample Usage:
#
# mysql::database::create { 'icinga2':
#   username   => 'icinga2',
#   password   => 'icinga2',
#   privileges => 'SELECT,INSERT,UPDATE,DELETE',
# }
#
define mysql::database::create ($username, $password, $privileges) {
  include mysql

  exec { "create-mysql-${name}-db":
    unless  => "mysql -u${username} -p${password} ${name}",
    command => "mysql -uroot -e \"CREATE DATABASE ${name}; \
GRANT ${privileges} ON ${name}.* TO ${username}@localhost \
IDENTIFIED BY '${password}';\"",
    require => Class['mysql']
  }
}
