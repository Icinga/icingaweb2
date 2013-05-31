#!/bin/sh

phpcs -p --standard=PSR2 --extensions=php --encoding=utf-8 --report-checkstyle=../../build/log/phpcs_results.xml --ignore=vendor "$@" ../..

exit 0
