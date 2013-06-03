#!/bin/sh

for filepath in `find ../doc -type f -name "*.md"`; do
    markdown $filepath > `dirname $filepath`/`basename $filepath .md`.html
done

exit 0
