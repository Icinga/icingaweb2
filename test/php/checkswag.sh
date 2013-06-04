#!/bin/sh

set -o nounset

SCRIPTNAME=$(readlink -f $0)
DIR=$(dirname $SCRIPTNAME)
PHPCS=$(which phpcs)

if [[ ! -x $PHPCS ]]; then
    echo "PHPCS not found!"
    exit 1
fi

# Make sure that the destination directory for logs and reports exists
mkdir -p $DIR/../../build/log

phpcs -p --standard=PSR2 --extensions=php --encoding=utf-8 \
    --report-checkstyle=$DIR/../../build/log/phpcs_results.xml \
    "$@" \
    $DIR/../../application \
    $DIR/../../library/Icinga \
    $DIR/../../bin

exit 0
