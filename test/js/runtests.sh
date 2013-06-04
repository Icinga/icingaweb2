#!/bin/sh

set -o nounset

SCRIPTNAME=$(readlink -f $0)
DIR=$(dirname $SCRIPTNAME)
MOCHA=$(which mocha)
DEFAULT="--recursive --require should"

if [[ ! -x $MOCHA ]]; then
    echo "mocha not found!";
    exit 1
fi

# Make sure that the destination directory for logs and reports exists
mkdir -p $DIR/../../build/log

cd $DIR

# Don't know where node modules are
export NODE_PATH=.:/usr/local/lib/node_modules:/usr/lib/node_modules

$MOCHA --reporter "xunit" $DEFAULT "$@" . > $DIR/../../build/log/mocha_results.xml
$MOCHA --reporter "mocha-cobertura-reporter" $DEFAULT "$@" . > $DIR/../../build/log/mocha_coverage.xml

exit 0