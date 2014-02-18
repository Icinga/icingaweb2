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

namespace Icinga\Web;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use RecursiveRegexIterator;
use Zend_Controller_Front;
use Icinga\Application\Icinga;
use lessc;

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
    
    private $source;

    /**
     * Create a new instance
     */
    public function __construct()
    {
        require_once 'vendor/lessphp/lessc.inc.php';
        $this->lessc = new lessc();

        $this->lessc->setVariables(
            array(
                'baseurl' => '\'' . Zend_Controller_Front::getInstance()->getBaseUrl(). '\''
            )
        );
    }

    public function compress()
    {
        $this->lessc->setPreserveComments(false);
        $this->lessc->setFormatter('compressed');
        return $this;
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

    public function addLoadedModules()
    {
        foreach (Icinga::app()->getModuleManager()->getLoadedModules() as $name => $module) {
            $this->addModule($name, $module);
        }
        return $this;
    }

    public function addFile($filename)
    {
        $this->source .= "\n/* CSS: $filename */\n"
            . file_get_contents($filename)
            . "\n\n";
        return $this;
    }

    public function compile()
    {
        //TODO:
/*        $tmpfile = '/tmp/icinga.less';
        $cssfile = '/tmp/icinga.css';
        if (! file_exists($tmpfile)) {
            file_put_contents($tmpfile, $this->source);
        }
        if ($this->lessc->checkedCompile($tmpfile, $cssfile)) {
        }
        return file_get_contents($cssfile);
*/
        return $this->lessc->compile($this->source);
    }

    public function addModule($name, $module)
    {
        if ($module->hasCss()) {
            $this->source .= "\n/* CSS: modules/$name/module.less */\n"
                . '.icinga-module.module-'
                . $name
                . " {\n"
                . file_get_contents($module->getCssFilename())
                . "}\n\n"
            ;
        }
        return $this;
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
            try {
                echo $this->lessc->compileFile($file);
            } catch (Exception $e) {
                echo '/* ' . PHP_EOL . ' ===' . PHP_EOL;
                echo '  Error in file ' . $file . PHP_EOL;
                echo '  ' . $e->getMessage() . PHP_EOL . PHP_EOL;
                echo '  ' . 'This file was dropped cause of errors.' . PHP_EOL;
                echo ' ===' . PHP_EOL . '*/' . PHP_EOL;
            }
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
