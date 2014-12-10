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
  Exec { path => '/bin:/usr/bin' }

  pgsql::database::create { $name:
    username => $username,
    password => $password,
  }

  exec { "populate-${name}-pgsql-db":
    onlyif  => "psql -U ${username} -d ${name} -c \"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '${name}';\" 2>/dev/null |grep -qEe '^ *0 *$'",
    command => "psql -U ${username} -d ${name} < ${schemafile}",
    user    => 'postgres',
    require => Pgsql::Database::Create[$name],
  }
}
