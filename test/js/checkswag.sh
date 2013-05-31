#!/bin/sh

jshint --reporter=jslint "$@" ../.. > ../../build/log/jshint_results.xml

exit 0
