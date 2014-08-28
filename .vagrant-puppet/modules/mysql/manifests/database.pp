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

define mysql::database::populate ($username, $password, $privileges, $schemafile) {
  mysql::database::create { $name:
    username => $username,
    password => $password,
    privileges => $privileges,
  }

  exec { "populate-${name}-mysql-db":
    unless  => "mysql -u${username} -p${password} ${name} -e \"SELECT * FROM icinga_dbversion;\" &> /dev/null",
    command => "mysql -uroot ${name} < ${schemafile}",
    require => Mysql::Database::Create[$name],
  }
}
