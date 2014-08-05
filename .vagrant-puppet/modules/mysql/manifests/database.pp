define mysql::database ($username, $password, $schemafile, $requirement) {
  include mysql

  exec { "create-mysql-${name}-db":
    unless  => "mysql -u${username} -p${password} ${name}",
    command => "mysql -uroot -e \"CREATE DATABASE ${name}; \
GRANT SELECT,INSERT,UPDATE,DELETE ON ${name}.* TO ${username}@localhost \
IDENTIFIED BY '${password}';\"",
    require => Service['mysqld']
  }

  exec { "populate-${name}-mysql-db":
    unless  => "mysql -u${username} -p${password} ${name} -e \"SELECT * FROM icinga_dbversion;\" &> /dev/null",
    command => "mysql -uroot ${name} < ${schemafile}",
    require => [ $requirement, Exec["create-mysql-${name}-db"] ]
  }
}
