class profile::nodejs {
  include epel

  exec { 'install nodejs':
    command => 'yum -d 0 -e 0 -y --enablerepo=epel install npm',
    unless  => 'rpm -qa | grep ^npm',
    require => Class['epel'],
  }

  exec { 'install npm/mocha':
    command => 'npm install -g mocha',
    creates => '/usr/lib/node_modules/mocha',
    require => Exec['install nodejs'],
  }

  exec { 'install npm/mocha-cobertura-reporter':
    command => 'npm install -g mocha-cobertura-reporter',
    creates => '/usr/lib/node_modules/mocha-cobertura-reporter',
    require => Exec['install npm/mocha'],
  }

  exec { 'install npm/jshint':
    command => 'npm install -g jshint',
    creates => '/usr/lib/node_modules/jshint',
    require => Exec['install nodejs'],
  }

  exec { 'install npm/expect':
    command => 'npm install -g expect',
    creates => '/usr/lib/node_modules/expect',
    require => Exec['install nodejs'],
  }

  exec { 'install npm/should':
    command => 'npm install -g should',
    creates => '/usr/lib/node_modules/should',
    require => Exec['install nodejs'],
  }

  exec { 'install npm/URIjs':
    command => 'npm install -g URIjs',
    creates => '/usr/lib/node_modules/URIjs',
    require => Exec['install nodejs'],
  }
}
