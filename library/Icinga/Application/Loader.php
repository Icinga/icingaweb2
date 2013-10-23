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

use Icinga\Exception\ProgrammingError;

class Loader
{
    /**
     * Namespace separator
     */
    const NAMESPACE_SEPARATOR = '\\';

    /**
     * List of namespaces
     * @var array
     */
    private $namespaces = array();

    /**
     * Detach spl autoload method from stack
     */
    public function __destruct()
    {
        $this->unRegister();
    }

    /**
     * Register new namespace for directory
     * @param string $namespace
     * @param string $directory
     * @throws \Icinga\Exception\ProgrammingError
     */
    public function registerNamespace($namespace, $directory)
    {
        if (!is_dir($directory)) {
            throw new ProgrammingError('Directory does not exist: '. $directory);
        }

        $this->namespaces[$namespace] = $directory;
    }

    /**
     * Test if a namespace exists
     * @param string $namespace
     * @return bool
     */
    public function hasNamespace($namespace)
    {
        return array_key_exists($namespace, $this->namespaces);
    }

    /**
     * Class loader
     *
     * Ignores all but classes in the Icinga namespace.
     *
     * @param string $class
     * @return boolean
     */
    public function loadClass($class)
    {
        $namespace = $this->getNamespaceForClass($class);

        if ($namespace) {
            $file = $this->namespaces[$namespace]. preg_replace('/^'. preg_quote($namespace). '/', '', $class);

            $file = str_replace(self::NAMESPACE_SEPARATOR, '/', $file). '.php';

            if (@file_exists($file)) {
                require_once $file;
                return true;
            }
        }

        return false;
    }

    /**
     * Test if we have a registered namespaces for this class
     *
     * Return is the longest match in the array found
     *
     * @param string $className
     * @return bool|string
     */
    private function getNamespaceForClass($className)
    {
        $testNamespace = '';
        $testLength = 0;

        foreach ($this->namespaces as $namespace => $directory) {
            $stub = preg_replace('/^'. preg_quote($namespace). '/', '', $className);
            $length = strlen($className) - strlen($stub);
            if ($length > $testLength) {
                $testLength = $length;
                $testNamespace = $namespace;
            }
        }

        if ($testLength > 0) {
            return $testNamespace;
        }

        return false;
    }

    /**
     * Effectively registers the autoloader the PHP/SPL way
     */
    public function register()
    {
        // Think about to add class pathes to php include path
        // this could be faster (tg)
        spl_autoload_register(array(&$this, 'loadClass'));
    }

    /**
     * Detach autoloader from spl registration
     */
    public function unRegister()
    {
        spl_autoload_unregister(array(&$this, 'loadClass'));
    }
}
