#!/bin/bash

installJquery () {
    # The npm module jquery won't install via puppet because of an mysterious error
    # when node-gyp rebuilding the dependent contextify module
    if [ ! -d /usr/lib/node_modules/jquery ]; then
        npm install --silent -g jquery
    fi
}

mountIcinga2webConfd () {
    # Remount /vagrant/config with appropriate permissions since the group apache is missing initially
    mount -t vboxsf -o uid=`id -u vagrant`,gid=`id -g apache`,dmode=775,fmode=775 v-icinga2web-conf.d /vagrant/config
}

installJquery
mountIcinga2webConfd
