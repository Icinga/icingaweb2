# Define: mysql::database::populate
#
#   Create and populate a MySQL database
#
# Parameters:
#
#   [*username*]   - name of the user the database belongs to
#   [*password*]   - password of the user the database belongs to
#   [*privileges*] - privileges of the user the database belongs to
#   [*schemafile*] - file with the schema for the database
#
# Requires:
#
#   mysql::database::create
#
# Sample Usage:
#
# mysql::database::populate { 'icinga2':
#   username   => 'icinga2',
#   password   => 'icinga2',
#   privileges => 'SELECT,INSERT,UPDATE,DELETE',
#   schemafile => '/usr/share/icinga2-ido-mysql/schema/mysql.sql',
# }
#
define mysql::database::populate ($username, $password, $privileges, $schemafile) {
  Exec { path => '/usr/bin' }

  mysql::database::create { $name:
    username   => $username,
    password   => $password,
    privileges => $privileges,
  }

  exec { "populate-${name}-mysql-db":
    unless  => "mysql -u${username} -p${password} ${name} -e \"SELECT * FROM icinga_dbversion;\" &> /dev/null",
    command => "mysql -uroot ${name} < ${schemafile}",
    require => Mysql::Database::Create[$name],
  }
}
