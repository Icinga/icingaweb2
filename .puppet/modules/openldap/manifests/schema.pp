# define: openldap::schema
#
#   Install a schema.
#
# Parameters:
#
# Actions:
#
# Requires:
#
# Sample Usage:
#
define openldap::schema {

  include openldap

  exec { "openldap-schema-${name}":
    command => "ldapadd -Y EXTERNAL -H ldapi:// -f /etc/openldap/schema/${name}.ldif",
    group   => 'root',
    require => Service['slapd'],
    unless  => "test -n \"$(find /etc/openldap/slapd.d/cn=config/cn=schema/ -name cn={*}${name}.ldif -print -quit)\"",
    user    => 'root',
  }
}
