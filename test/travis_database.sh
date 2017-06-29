#!/bin/bash

set -ex

mysql -u root -e "GRANT ALL ON *.* TO icinga_unittest@localhost IDENTIFIED BY 'icinga_unittest'"

export PGHOST=localhost
export PGUSER=postgres

psql -c "CREATE USER icinga_unittest WITH PASSWORD 'icinga_unittest'"
psql -c "CREATE DATABASE icinga_unittest WITH OWNER icinga_unittest"
