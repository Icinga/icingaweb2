<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use RecursiveRegexIterator;
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

    private $source;

    /**
     * Create a new instance
     */
    public function __construct()
    {
        require_once 'lessphp/lessc.inc.php';
        $this->lessc = new lessc();
    }

    /**
     * Disable the extendend import functionality
     *
     * @return  $this
     */
    public function disableExtendedImport()
    {
        $this->lessc->importDisabled = true;
        return $this;
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
        return $this->lessc->compile($this->source);
    }

    public function addModule($name, $module)
    {
        if ($module->hasCss()) {
            $contents = array();
            foreach ($module->getCssFiles() as $path) {
                if (file_exists($path)) {
                    $contents[] = "/* CSS: modules/$name/$path */\n" . file_get_contents($path);
                }
            }

            $this->source .= ''
                . '.icinga-module.module-'
                . $name
                . " {\n"
                . join("\n\n", $contents)
                . "}\n\n";
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
