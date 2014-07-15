#!/usr/bin/python

# {{{ICINGA_LICENSE_HEADER}}}
# This file is part of Icinga Web 2.
#
# Icinga Web 2 - Head for multiple monitoring backends.
# Copyright (C) 2013 Icinga Development Team
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
# @copyright  2013 Icinga Development Team <info@icinga.org>
# @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
# @author     Icinga Development Team <info@icinga.org>
#
# {{{ICINGA_LICENSE_HEADER}}}

import sys
import logging
import optparse
import re
import os
import shutil
import time
from cStringIO import StringIO

MAX_LINE_LENGTH = 80

FILE_TYPE_CONFIG = {
    'php': {'prefix': ' * ',
            'firstComment': '/**',
            'lastComment': ' */',
            'linesBefore': 0,
            'linesAfter': 0},
    'js': {'prefix': ' * ',
           'firstComment': '/**',
           'lastComment': ' */',
           'linesBefore': 0,
           'linesAfter': 0},
    'py': {'prefix': '# ',
           'firstComment': None,
           'lastComment': None,
           'linesBefore': 0,
           'linesAfter': 0},
    'less': {'prefix': ' * ',
            'firstComment': '/**',
            'lastComment': ' */',
            'linesBefore': 0,
            'linesAfter': 0}
}

REPLACE_TOKENS = {
    'YEAR': time.strftime('%Y')
}

SECTION_MARKER = re.compile(r'\{\{\{ICINGA_LICENSE_HEADER\}\}\}')

LICENSE_DATA = None

__version__ = '1.0'

__LICENSE_STORE = {}

__SUFFIX_MATCHER = None


class LogFormatter(logging.Formatter):
    """Log formatter with color support which is enabled automaticallyby importing it."""

    def __init__(self, *args, **kwargs):
        logging.Formatter.__init__(self, *args, **kwargs)
        self._color = sys.stderr.isatty()
        if self._color:
            self._colors = {
                logging.DEBUG: ('\x1b[34m',), # Blue
                logging.INFO: ('\x1b[32m',), # Green
                logging.WARNING: ('\x1b[33m',), # Yellow
                logging.ERROR: ('\x1b[31m',), # Red
                logging.CRITICAL: ('\x1b[1m', '\x1b[31m'), # Bold, Red
            }
            self._footer = '\x1b[0m'

    def format(self, record):
        formatted_message = logging.Formatter.format(self, record)
        if self._color:
            formatted_message = (''.join(self._colors.get(record.levelno)) + formatted_message +
                                 len(self._colors.get(record.levelno)) * self._footer)
        return formatted_message


def add_optparse_logging_options(parser, default_loglevel='DEBUG'):
    """Add log levels to option parser"""
    LOGLEVELS = ('INFO', 'WARNING', 'ERROR', 'CRITICAL', 'DEBUG')
    parser.add_option('-v', '--verbose', dest='logging_level', default=default_loglevel, choices=LOGLEVELS,
                      help="Print verbose informational messages. One of %s. [default: %%default]" % ', '.join(
                          LOGLEVELS))


def init_logging(loglevel):
    """Initialize loglevels"""
    channel = logging.StreamHandler()
    channel.setFormatter(LogFormatter(fmt='%(asctime)-15s: %(message)s', datefmt='%Y-%m-%d %H:%M:%S'))
    logging.getLogger().addHandler(channel)
    logging.getLogger().setLevel(getattr(logging, loglevel))
    return logging.getLogger(__name__)


def init_optparse():
    """Initialize opt parser"""
    parser = optparse.OptionParser(usage='%prog -d <DIR> -L <license> -B <suffix>', version='%%prog %s' % __version__)

    parser.add_option("-d", "--directory", action="append", type="string", dest="dir",
                      help="Directory, multiple switches possible")

    parser.add_option("-L", "--license", action="store", type="string", dest="license",
                      help="Path to license file")

    parser.add_option("-B", "--backup", action="store", type="string", dest="backup",
                      help="Backup suffix, e.g. '.BAK'")
    add_optparse_logging_options(parser)
    return parser


def match_file_suffix(file_name):
    """Test if er have configuration for this file"""
    global __SUFFIX_MATCHER
    if __SUFFIX_MATCHER == None:
        keys = FILE_TYPE_CONFIG.keys()
        match = r'\.(' + '|'.join(keys) + r')$'
        __SUFFIX_MATCHER = re.compile(match)
    return __SUFFIX_MATCHER.search(file_name)


def load_files(dirs):
    """Load all files found into an array"""
    filelist = []
    for directory in dirs:
        for root, subFolders, files in os.walk(directory):
            for file_name in files:
                if match_file_suffix(file_name):
                    filelist.append(os.path.join(root, file_name))
    return filelist

def count_regex_matches(pattern, string):
    """Counting regex matchings, e.g. tokens in a file"""
    total = 0
    start = 0
    while True:
        match_object = pattern.search(string, start)
        if match_object is None:
            return total
        total += 1

        start = match_object.start() + 1

def test_license_token(data, file_name):
    """Test if we have a valid license token in a file"""
    global SECTION_MARKER
    c = count_regex_matches(SECTION_MARKER, data)
    log = logging.getLogger(__name__)

    if c == 2:
        return True
    elif c == 0:
        log.warn('No license token in file %s', file_name)
    elif c < 2:
        log.error('Incomplete license token in file %s', file_name)
    else:
        log.error('More that one license token in file %s', file_name)

    return False

def get_license(type):
    """Creates license data for a specific configuration"""
    global FILE_TYPE_CONFIG
    global LICENSE_DATA
    global REPLACE_TOKENS
    global __LICENSE_STORE

    try:
        return __LICENSE_STORE[type]
    except(KeyError):
        if not LICENSE_DATA:
            __LICENSE_STORE[type] = ''
            return ''
        config = FILE_TYPE_CONFIG[type]
        license_data = []
        license_data.extend([''] * config['linesBefore'])
        if config['firstComment'] != None:
            license_data.append(config['firstComment'])
        for line in LICENSE_DATA.split('\n'):
            if line:
                license_data.append(config['prefix'] + line)
            else:
                # Whitespace is uselses in this case (#4603)
                license_data.append(config['prefix'].rstrip())
        if config['lastComment'] != None:
            license_data.append(config['lastComment'])
        license_data.extend([''] * config['linesAfter'])
        __LICENSE_STORE[type] = '\n'.join(license_data)
        __LICENSE_STORE[type] = __LICENSE_STORE[type] % REPLACE_TOKENS

    return __LICENSE_STORE[type]

def read_file_content(file_name):
    """Read file into a string"""
    fhandle = open(file_name, 'r')
    content = fhandle.read()
    fhandle.close
    return content

def write_file_content(file_name, content):
    """Write a string into a file"""
    fhandle = open(file_name, 'w')
    fhandle.write(content)
    fhandle.close()

def replace_text(org_data, license_data):
    """Replace the license token in the string"""
    shandle = StringIO(org_data)
    out = ''
    test = False

    while True:
        line = shandle.readline()

        if line == '':
            break

        if SECTION_MARKER.search(line) and test == False:
            test = True
        elif SECTION_MARKER.search(line) and test == True:
            test = False
            if license_data:
                out += license_data
                out += '\n'
        elif test == True:
            continue

        out += line
    shandle.close()
    return out

def process_files(files, backup):
    """Iterate over files and trigger reokacement"""
    global FILE_TYPE_CONFIG
    log = logging.getLogger(__name__)
    for file_name in files:
        data = read_file_content(file_name)
        if test_license_token(data, file_name):
            base, ext = os.path.splitext(file_name)
            ext = ext[1:]
            try:
                config =  FILE_TYPE_CONFIG[ext]

                license_data = get_license(ext)
                new_data = replace_text(data, license_data)

                if data != new_data:
                    log.info('File changed: %s', file_name)

                    if backup:
                        newfile = file_name + backup
                        log.info('Backup %s to %s', file_name, newfile)
                        shutil.copy(file_name, newfile)

                    write_file_content(file_name, new_data)

                    log.info('Written to file: %s', file_name)

            except (KeyError):
                log.error('No header config for file type %s', ext)
                continue




def main():
    """Main script entry point"""
    global LICENSE_DATA

    parser = init_optparse()
    (options, args) = parser.parse_args()
    log = init_logging(options.logging_level)

    if options.dir is None or not len(options.dir) or not options.license:
        log.error('--dir and --license are mandatory')
        parser.print_help()
        return (1)

    log.debug('starting')

    LICENSE_DATA = read_file_content(options.license)

    log.info('Scanning directories ...')
    files = load_files(options.dir)
    log.info('Got %d matching ones', len(files))

    log.info('Processing files ...')
    process_files(files, options.backup)


if __name__ == '__main__':
    sys.exit(main())
