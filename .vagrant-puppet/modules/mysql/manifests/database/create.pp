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
