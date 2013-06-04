#!/bin/sh

set -o nounset

SCRIPTNAME=$(readlink -f $0)
DIR=$(dirname $SCRIPTNAME)
PHPUNIT=$(which phpunit)

if [[ ! -x $PHPUNIT ]]; then
    echo "PHPUnit not found!"
    exit 1
fi

# Make sure that the destination directory for logs and reports exists
mkdir -p $DIR/../../build/log

cd $DIR

$PHPUNIT "$@" 

exit 0
