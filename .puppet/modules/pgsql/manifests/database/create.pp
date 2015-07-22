# Define: pgsql::database::create
#
#   Create a PgSQL database
#
# Parameters:
#
#   [*username*]   - name of the user the database belongs to
#   [*password*]   - password of the user the database belongs to
#
# Requires:
#
#   pgsql
#
# Sample Usage:
#
# pgsql::database::create { 'icinga2':
#   username   => 'icinga2',
#   password   => 'icinga2',
# }
#
define pgsql::database::create ($username, $password) {
  include pgsql

  exec { "create-pgsql-${name}-db":
    unless  => "psql -tAc \"SELECT 1 FROM pg_roles WHERE rolname='${username}'\" | grep -q 1",
    command => "psql -c \"CREATE ROLE ${username} WITH LOGIN PASSWORD '${password}';\" && \
createdb -O ${username} -E UTF8 -T template0 ${name} && \
(createlang plpgsql ${name} || true)",
    user    => 'postgres',
    require => Class['pgsql']
  }
}
