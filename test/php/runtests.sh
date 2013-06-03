#!/bin/sh

# Make sure that the destination directory for logs and reports exists
mkdir -p ../../build/log

phpunit "$@" .

exit 0
