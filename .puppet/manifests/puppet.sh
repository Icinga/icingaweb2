#!/bin/bash

set -e

if which puppet >/dev/null 2>&1; then
    exit 0
fi

RELEASEVER=$(rpm -q --qf "%{VERSION}" $(rpm -q --whatprovides redhat-release))

case $RELEASEVER in
    6|7)
        PUPPET="http://yum.puppetlabs.com/puppetlabs-release-el-${RELEASEVER}.noarch.rpm"
        ;;
    *)
        echo "Unknown release version: $RELEASEVER" >&2
        exit 1
        ;;
esac

echo "Adding puppet repository.."
rpm --import "https://yum.puppetlabs.com/RPM-GPG-KEY-puppetlabs"
rpm -Uvh $PUPPET >/dev/null

echo "Installing puppet.."
yum install -y puppet >/dev/null
