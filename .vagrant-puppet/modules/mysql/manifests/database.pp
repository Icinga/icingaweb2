define mysql::database ($username, $password) {
  include mysql

  exec { "create-mysql-${name}-db":
    unless  => "mysql -u${username} -p${password} ${name}",
    command => "mysql -uroot -e \"CREATE DATABASE ${name}; \
GRANT SELECT,INSERT,UPDATE,DELETE ON ${name}.* TO ${username}@localhost \
IDENTIFIED BY '${password}';\"",
    require => Service['mysqld']
  }
}
