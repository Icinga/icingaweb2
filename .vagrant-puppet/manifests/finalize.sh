#!/bin/bash

set -e

installJquery () {
    # The npm module jquery won't install via puppet because of an mysterious error
    # when node-gyp rebuilding the dependent contextify module
    if [ ! -d /usr/lib/node_modules/jquery ]; then
        npm install --silent -g jquery
    fi
}

mountIcinga2webConfd () {
    # Remount /vagrant/config with appropriate permissions since the group apache is missing initially
    mount -t vboxsf -o uid=`id -u vagrant`,gid=`id -g apache`,dmode=775,fmode=775 /vagrant/config /vagrant/config
}

startServicesWithNonLSBCompliantExitStatusCodes () {
    # Unfortunately the ido2db init script is not LSB compliant and hence not started via puppet
    service ido2db-mysql start || true
    service ido2db-pgsql start || true
}

installJquery
mountIcinga2webConfd
startServicesWithNonLSBCompliantExitStatusCodes

exit 0
