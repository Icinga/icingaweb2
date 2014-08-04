define mysql::database (
  $username = 'UNDEF',
  $password = 'UNDEF'
) {
  include mysql

  $user = $username ? {
    /UNDEF/ => $name,
    default => $username,
  }
  $pass = $password ? {
    /UNDEF/ => $user,
    default => $password,
  }

  exec { "create-mysql-${name}-db":
    unless  => "mysql -u${user} -p${pass} ${name}",
    command => "mysql -uroot -e \"CREATE DATABASE ${name}; \
GRANT SELECT,INSERT,UPDATE,DELETE ON ${name}.* TO ${user}@localhost \
IDENTIFIED BY '${pass}';\"",
    require => Service['mysqld']
  }
}
