#!/bin/sh

set -o nounset

SCRIPTNAME=$(readlink -f $0)
DIR=$(dirname $SCRIPTNAME)

# Make sure that the destination directory for logs and reports exists
mkdir -p $DIR/build/log

mocha --reporter "xunit" --recursive "$@" . > $DIR/build/log/mocha_results.xml
mocha --reporter "cobertura" --recursive "$@" . > $DIR/build/log/mocha_coverage.xml

exit 0
