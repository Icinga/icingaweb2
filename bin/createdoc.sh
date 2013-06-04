#!/bin/sh

set -o nounset

SCRIPTNAME=$(readlink -f $0)
DIR=$(dirname $SCRIPTNAME)

for filepath in `find $DIR/../doc -type f -name "*.md"`; do
    markdown $filepath > `dirname $filepath`/`basename $filepath .md`.html
done

exit 0
