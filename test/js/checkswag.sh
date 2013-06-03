#!/bin/sh

# Make sure that the destination directory for logs and reports exists
mkdir -p ../../build/log

jshint --reporter=jslint "$@" ../.. > ../../build/log/jshint_results.xml

exit 0
