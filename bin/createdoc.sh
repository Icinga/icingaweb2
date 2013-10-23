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

SCRIPTNAME=$(readlink -f $0)
DIR=$(dirname $SCRIPTNAME)

for filepath in `find $DIR/../doc -type f -name "*.md"`; do
    markdown $filepath > `dirname $filepath`/`basename $filepath .md`.html
done

exit 0
