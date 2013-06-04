#!/bin/sh

set -o nounset

SCRIPTNAME=$(readlink -f $0)
DIR=$(dirname $SCRIPTNAME)

# Make sure that the destination directory for logs and reports exists
mkdir -p $DIR/../../build/log

jshint --reporter=jslint "$@" $DIR/../.. > $DIR/../../build/log/jshint_results.xml

exit 0
