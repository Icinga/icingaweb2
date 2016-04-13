<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Icinga\Application\Logger;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use lessc;

/**
 * Compile LESS into CSS
 *
 * Comments will be removed always. lessc is messing them up.
 */
class LessCompiler
{
    /**
     * lessphp compiler
     *
     * @var lessc
     */
    protected $lessc;

    /**
     * Array of LESS files
     *
     * @var string[]
     */
    protected $lessFiles = array();

    /**
     * Array of module LESS files indexed by module names
     *
     * @var array[]
     */
    protected $moduleLessFiles = array();

    /**
     * LESS source
     *
     * @var string
     */
    protected $source;

    /**
     * Path of the LESS theme
     *
     * @var string
     */
    protected $theme;

    /**
     * Create a new LESS compiler
     */
    public function __construct()
    {
        require_once 'lessphp/lessc.inc.php';
        $this->lessc = new lessc();
        // Discourage usage of import because we're caching based on an explicit list of LESS files to compile
        $this->lessc->importDisabled = true;
    }

    /**
     * Add a Web 2 LESS file
     *
     * @param   string  $lessFile   Path to the LESS file
     *
     * @return  $this
     */
    public function addLessFile($lessFile)
    {
        $this->lessFiles[] = realpath($lessFile);
        return $this;
    }

    /**
     * Add a module LESS file
     *
     * @param   string  $moduleName Name of the module
     * @param   string  $lessFile   Path to the LESS file
     *
     * @return  $this
     */
    public function addModuleLessFile($moduleName, $lessFile)
    {
        if (! isset($this->moduleLessFiles[$moduleName])) {
            $this->moduleLessFiles[$moduleName] = array();
        }
        $this->moduleLessFiles[$moduleName][] = realpath($lessFile);
        return $this;
    }

    /**
     * Get the list of LESS files added to the compiler
     *
     * @return string[]
     */
    public function getLessFiles()
    {
        $lessFiles = $this->lessFiles;

        foreach ($this->moduleLessFiles as $moduleLessFiles) {
            $lessFiles = array_merge($lessFiles, $moduleLessFiles);
        }

        if ($this->theme !== null) {
            $lessFiles[] = $this->theme;
        }
        return $lessFiles;
    }

    /**
     * Set the path to the LESS theme
     *
     * @param   string  $theme  Path to the LESS theme
     *
     * @return  $this
     */
    public function setTheme($theme)
    {
        if (is_file($theme) && is_readable($theme)) {
            $this->theme = $theme;
        } else {
            Logger::error('Can\t load theme %s. Make sure that the theme exists and is readable', $theme);
        }
        return $this;
    }

    /**
     * Instruct the compiler to minify CSS
     *
     * @return  $this
     */
    public function compress()
    {
        $this->lessc->setFormatter('compressed');
        return $this;
    }

    /**
     * Render to CSS
     *
     * @return  string
     */
    public function render()
    {
        foreach ($this->lessFiles as $lessFile) {
            $this->source .= file_get_contents($lessFile);
        }

        $moduleCss = '';
        foreach ($this->moduleLessFiles as $moduleName => $moduleLessFiles) {
            $moduleCss .= '.icinga-module.module-' . $moduleName . ' {';
            foreach ($moduleLessFiles as $moduleLessFile) {
                $moduleCss .= file_get_contents($moduleLessFile);
            }
            $moduleCss .= '}';
        }

        $this->source .= $moduleCss;

        if ($this->theme !== null) {
            $this->source .= file_get_contents($this->theme);
        }

        return preg_replace(
            '/(\.icinga-module\.module-[^\s]+) (#layout\.[^\s]+)/m',
            '\2 \1',
            $this->lessc->compile($this->source)
        );
    }
}
