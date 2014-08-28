define pgsql::database::create ($username, $password) {
  include pgsql

  exec { "create-pgsql-${name}-db":
    unless  => "sudo -u postgres psql -tAc \"SELECT 1 FROM pg_roles WHERE rolname='${username}'\" | grep -q 1",
    command => "sudo -u postgres psql -c \"CREATE ROLE ${username} WITH LOGIN PASSWORD '${password}';\" && \
sudo -u postgres createdb -O ${username} -E UTF8 -T template0 ${name} && \
sudo -u postgres createlang plpgsql ${name}",
    require => Class['pgsql']
  }
}

define pgsql::database::populate ($username, $password, $schemafile) {
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
