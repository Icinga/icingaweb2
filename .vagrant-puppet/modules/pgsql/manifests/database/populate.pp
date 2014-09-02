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
