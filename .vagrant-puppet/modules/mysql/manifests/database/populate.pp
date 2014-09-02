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
