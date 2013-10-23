#!/bin/sh

# {{{ICINGA_LICENSE_HEADER}}}
# Icinga Web 2 - Head for multiple monitoring frontends
# Copyright (C) %(YEAR)s Icinga Development Team
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#
# @copyright 2013 Icinga Development Team <info@icinga.org>
# @author Icinga Development Team <info@icinga.org>
# {{{ICINGA_LICENSE_HEADER}}}

set -o nounset

DIR=$(readlink -f $(dirname $0)/../)
BIN=$(basename $0)
PHPDOC=$(which phpdoc)
CONFIG=$DIR/doc/phpdoc.xml
OUTPUT=$DIR/doc/api
ARG=${1-}
BUILD=""

cd $DIR

if [ ! -x $PHPDOC ]; then
    echo "phpDocumentor not found (phpdoc)"
    echo "Please read http://phpdoc.org/docs/latest/for-users/installation.html how to install"
    exit 1
fi

if [ -d $OUTPUT ]; then
    echo "Output directory exists"
    echo "rm -rf $OUTPUT"
    rm -rf $OUTPUT
fi

if [ "$ARG" == "--build" ]; then
    BUILD="-q"
fi

if [ "$ARG" == "--help" ]; then
    echo "Usage $BIN [ --build ]"
    echo ""
    echo "Options:"
    echo "  --build  Silent output"
    echo "  --help   Print this screen"
    echo ""
    exit 1
fi

$PHPDOC $BUILD -c $CONFIG
exit $?
