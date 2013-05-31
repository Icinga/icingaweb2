#!/bin/sh

mocha --reporter "xunit" --recursive "$@" . > ../../build/log/mocha_results.xml
mocha --reporter "cobertura" --recursive "$@" . > ../../build/log/mocha_coverage.xml

exit 0
