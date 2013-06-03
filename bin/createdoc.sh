#!/bin/sh

for filepath in `find ../doc -type f -name "*.txt"`; do
    markdown $filepath > `dirname $filepath`/`basename $filepath .txt`.html
done

exit 0
