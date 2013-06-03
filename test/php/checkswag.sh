#!/bin/sh

set -o nounset

SCRIPTNAME=$(readlink -f $0)
DIR=$(dirname $SCRIPTNAME)

# Make sure that the destination directory for logs and reports exists
mkdir -p $DIR/../../build/log

phpcs -p --standard=PSR2 --extensions=php --encoding=utf-8 --report-checkstyle=$DIR/build/log/phpcs_results.xml --ignore=vendor "$@" ../..

exit 0
