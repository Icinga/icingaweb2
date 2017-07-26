#!/bin/bash

set -e

# If the installed version is outdated, try to update.

# The updater seems to try to install kernel-devel-$(uname -r) which is not
# available in case of an outdated kernel version.
# If the updater fails (for this reason), we try to upgrade the kernel in the
# hope that the updater will succeed on the next reboot.

ptiagent-cmd --ver || \
ptiagent-cmd --install || \
yum update kernel -y
