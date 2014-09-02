# Define: pgsql::database::populate
#
#   Create and populate a PgSQL database
#
# Parameters:
#
#   [*username*]   - name of the user the database belongs to
#   [*password*]   - password of the user the database belongs to
#   [*schemafile*] - file with the schema for the database
#
# Requires:
#
#   pgsql::database::create
#
# Sample Usage:
#
# pgsql::database::populate { 'icinga2':
#   username   => 'icinga2',
#   password   => 'icinga2',
#   schemafile => '/usr/share/icinga2-ido-pgsql/schema/pgsql.sql',
# }
#
define pgsql::database::populate ($username, $password, $schemafile) {
  Exec { path => '/usr/bin' }

  pgsql::database::create { $name:
    username => $username,
    password => $password,
  }

  exec { "populate-${name}-pgsql-db":
    unless  => "psql -U ${username} -d ${name} -c \"SELECT * FROM icinga_dbversion;\" &> /dev/null",
    command => "sudo -u postgres psql -U ${username} -d ${name} < ${schemafile}",
    require => Pgsql::Database::Create[$name],
  }
}
