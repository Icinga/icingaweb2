<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web;

use \Zend_Controller_Front;
use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;
use \RegexIterator;
use \RecursiveRegexIterator;

/**
 * Less compiler prints files or directories to stdout
 */
class LessCompiler
{
    /**
     * Collection of items: File or directories
     *
     * @var array
     */
    private $items = array();

    /**
     * lessphp compiler
     *
     * @var \lessc
     */
    private $lessc;

    private $baseUrl;

    /**
     * Create a new instance
     */
    public function __construct()
    {
        require_once 'vendor/lessphp/lessc.inc.php';
        $this->lessc = new \lessc();

        $this->lessc->setVariables(
            array(
                'baseurl' => '\'' . Zend_Controller_Front::getInstance()->getBaseUrl(). '\''
            )
        );
    }

    /**
     * Add usable style item to stack
     *
     * @param string $item File or directory
     */
    public function addItem($item)
    {
        $this->items[] = $item;
    }

    /**
     * Compile and print a single file
     *
     * @param string $file
     */
    public function printFile($file)
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        echo PHP_EOL. '/* CSS: ' . $file . ' */' . PHP_EOL;

        if ($ext === 'css') {
            readfile($file);
        } elseif ($ext === 'less') {
            echo $this->lessc->compileFile($file);
        }

        echo PHP_EOL;
    }

    /**
     * Compile and print a path content (recursive)
     *
     * @param string $path
     */
    public function printPathRecursive($path)
    {
        $directoryInterator = new RecursiveDirectoryIterator($path);
        $iterator = new RecursiveIteratorIterator($directoryInterator);
        $filteredIterator = new RegexIterator($iterator, '/\.(css|less)$/', RecursiveRegexIterator::GET_MATCH);
        foreach ($filteredIterator as $file => $extension) {
            $this->printFile($file);
        }
    }

    /**
     * Compile and print the whole item stack
     */
    public function printStack()
    {
        foreach ($this->items as $item) {
            if (is_dir($item)) {
                $this->printPathRecursive($item);
            } elseif (is_file($item)) {
                $this->printFile($item);
            }
        }
    }
}
