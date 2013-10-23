<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

class TranslationHelper
{
    protected $basedir;
    protected $moduledir;
    protected $tmpfile;
    protected $potfile;
    protected $locale;
    protected $module;

    public function __construct(ApplicationBootstrap $bootstrap, $locale, $module = null)
    {
        $this->moduledir  = $bootstrap->getModuleDir();
        if ($module) {
            $this->basedir = $bootstrap->getModuleDir($module) . '/application';
        } else {
            $this->basedir = $bootstrap->getApplicationDir();
        }
        $this->locale     = $locale;
        $this->module     = $module;
        $this->targetfile = $this->basedir
                          . '/locale/'
                          . $this->locale
                          . '/LC_MESSAGES/'
                          . ($module ? $module : 'icinga')
                          . '.po';
        $target_dir = dirname($this->targetfile);
        if (! is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
    }

    public function __destruct()
    {
        if ($this->tmpfile !== null) {
            unlink($this->tmpfile);
        }
        if ($this->potfile !== null) {
            unlink($this->potfile);
        }
    }

    public function extractTexts()
    {
        $tmpdir = sys_get_temp_dir();
        $this->potfile = tempnam($tmpdir, 'IcingaPot_');
        $cmd = '/usr/bin/xgettext'
             . ' --language=PHP'
             . ' --from-code=iso-8859-15'
             . ' --keyword='
             . ($this->module ? '_mt:2' : '_t')
             . ' --sort-output'
             . ' --force-po'
             . ' --package-name=Icinga'
             . ' --package-version=0.1'
             . ' --copyright-holder="Icinga Team"'
             . ' --msgid-bugs-address="dev@icinga.org"'
             . ' --files-from=' . $this->tmpfile
             . ' --output=' . $this->potfile
             ;
         `$cmd`;
         $this->fixPotfile();
         $this->mergeOldTranslations();
         return $this;
    }

    protected function fixPotfile()
    {
        $content = file_get_contents($this->potfile);
        $fh = fopen($this->potfile, 'w');
        foreach (preg_split('~\n~', $content) as $line) {
            // if (preg_match('~^"Language:~', $line)) continue;
            if (preg_match('~^"Content-Type:~', $line)) {
                $line = '"Content-Type: text/plain; charset=utf-8\n"';
            }
            fwrite($fh, $line . "\n");
        }
        fclose($fh);
    }

    protected function mergeOldTranslations()
    {
        if (is_file($this->targetfile)) {
            $cmd = sprintf(
                '/usr/bin/msgmerge %s %s -o %s 2>&1',
                $this->targetfile,
                $this->potfile,
                $this->targetfile . '.new'
            );
            `$cmd`;
            rename($this->targetfile . '.new', $this->targetfile);
        } else {
            file_put_contents($this->targetfile, file_get_contents($this->potfile));
        }
    }

    public function createTemporaryFileList()
    {
        $tmpdir = sys_get_temp_dir();
        $this->tmpfile = tempnam($tmpdir, 'IcingaTranslation_');
        $tmp_fh = fopen($this->tmpfile, 'w');
        if (! $tmp_fh) {
            throw new \Exception('Unable to create ' . $this->tmpfile);
        }
        if ($this->module) {
            $blacklist = array();
        } else {
            $blacklist = array(
                $this->moduledir
            );
        }
        $this->getSourceFileNames($this->basedir, $tmp_fh, $blacklist);
        $this->getSourceFileNames(ICINGA_LIBDIR, $tmp_fh, $blacklist);
        fclose($tmp_fh);
        return $this;
    }

    protected function getSourceFileNames($dir, & $fh, $blacklist = array())
    {
        $dh = opendir($dir);
        if (! $dh) {
            throw new \Exception("Unable to read files from $dir");
        }
        $subdirs = array();
        while ($filename = readdir($dh)) {
            if ($filename[0] === '.') {
                continue;
            }
            $fullname = $dir . '/' . $filename;
            if (preg_match('~\.(?:php|phtml)$~', $filename)) {
                fwrite($fh, "$fullname\n");
            } elseif (is_dir($fullname)) {
                if (in_array($fullname, $blacklist)) {
                    continue;
                }
                $subdirs[] = $fullname;
            }
        }
        closedir($dh);
        foreach ($subdirs as $dir) {
            $this->getSourceFileNames($dir, $fh, $blacklist);
        }
    }
}
