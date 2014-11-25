#!/bin/bash

set -e

mountIcinga2webVarLog () {
    if ! $(/bin/mount | /bin/grep -q "/vagrant/var/log"); then
        # Remount /vagrant/var/log/ with appropriate permissions since the group apache is missing initially
        /bin/mount -t vboxsf -o \
            uid=`id -u vagrant`,gid=`id -g apache`,dmode=775,fmode=664 \
            /vagrant/var/log/ \
            /vagrant/var/log/
    fi
}

mountIcinga2webVarLog

exit 0
