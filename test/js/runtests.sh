#!/bin/sh

# Make sure that the destination directory for logs and reports exists
mkdir -p ../../build/log

mocha --reporter "xunit" --recursive "$@" . > ../../build/log/mocha_results.xml
mocha --reporter "cobertura" --recursive "$@" . > ../../build/log/mocha_coverage.xml

exit 0
