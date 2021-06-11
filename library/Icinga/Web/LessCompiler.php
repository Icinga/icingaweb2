<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Icinga\Application\Logger;
use Icinga\Util\LessParser;

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
     * @var LessParser
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
     * Array of module names indexed by LESS asset paths
     *
     * @var array
     */
    protected $moduleRequires = [];

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
        $this->lessc = new LessParser();
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
     * Add a LESS asset requirement
     *
     * @param   string  $moduleName
     * @param   string  $lessFile
     *
     * @return  $this
     */
    public function addModuleRequire($moduleName, $lessFile)
    {
        $this->moduleRequires[$lessFile][] = $moduleName;
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

        $lessFiles = array_merge($lessFiles, array_keys($this->moduleRequires));

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
        $exportedVars = [];
        foreach ($this->moduleLessFiles as $moduleName => $moduleLessFiles) {
            $moduleCss .= '.icinga-module.module-' . $moduleName . ' {';

            // TODO: Import these.
            foreach ($this->moduleRequires as $requiredFile => $modules) {
                if (in_array($moduleName, $modules, true)) {
                    $moduleCss .= file_get_contents($requiredFile);
                }
            }

            foreach ($moduleLessFiles as $moduleLessFile) {
                $content = file_get_contents($moduleLessFile);

                $pattern = '/^@exports:\s*{((?:\s*@[^:}]+:[^;]*;\s+)+)};$/m';
                if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $content = str_replace($match[0], '', $content);
                        foreach (explode("\n", trim($match[1])) as $line) {
                            list($name, $value) = explode(':', $line, 2);
                            $exportedVars[trim($name)] = trim($value, ' ;');
                        }
                    }
                }

                $moduleCss .= $content;
            }

            $moduleCss .= '}';
        }

        $this->source .= $moduleCss;

        $varExports = '';
        foreach ($exportedVars as $name => $value) {
            $varExports .= sprintf("%s: %s;\n", $name, $value);
        }

        // exported vars are injected at the beginning to avoid that they are
        // able to override other variables, that's what themes are for
        $this->source = $varExports . "\n\n" . $this->source;

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
