<?php
// @codeCoverageIgnoreStart
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

use Icinga\Exception\ProgrammingError;
use Icinga\Util\Translator;
use Zend_View_Abstract;
use Closure;

/**
 * Icinga view
 */
class View extends Zend_View_Abstract
{
    /**
     * Charset to be used - we only support UTF-8
     */
    const CHARSET = 'UTF-8';

    /**
     * The flags we use for htmlspecialchars depend on our PHP version
     */
    private $replaceFlags;

    /**
     * Flag to register stream wrapper
     *
     * @var bool
     */
    private $useViewStream = false;

    /**
     * Registered helper functions
     */
    private $helperFunctions = array();

    /**
     * Create a new view object
     *
     * @param array $config
     * @see Zend_View_Abstract::__construct
     */
    public function __construct($config = array())
    {
        $this->useViewStream = (bool) ini_get('short_open_tag') ? false : true;
        if ($this->useViewStream) {
            if (!in_array('zend.view', stream_get_wrappers())) {
                stream_wrapper_register('zend.view', '\Icinga\Web\ViewStream');
            }
        }

        if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
            $this->replaceFlags = ENT_COMPAT | ENT_SUBSTITUTE | ENT_HTML5;
        } else {
            $this->replaceFlags = ENT_COMPAT | ENT_IGNORE;
        }

        parent::__construct($config);
    }

    /**
     * Initialize the view
     *
     * @see Zend_View_Abstract::init
     */
    public function init()
    {
        $this->loadGlobalHelpers();
    }

    /**
     * Escape the given value top be safely used in view scripts
     *
     * @param  string $value  The output to be escaped
     * @return string
     */
    public function escape($value)
    {
        return htmlspecialchars($value, $this->replaceFlags, self::CHARSET, true);
    }

    /**
     * Whether a specific helper (closure) has been registered
     *
     * @param  string $name The desired function name
     * @return boolean
     */
    public function hasHelperFunction($name)
    {
        return array_key_exists($name, $this->helperFunctions);
    }

    /**
     * Add a new helper function
     *
     * @param  string  $name     The desired function name
     * @param  Closure $function An anonymous function
     * @return self
     */
    public function addHelperFunction($name, Closure $function)
    {
        if ($this->hasHelperFunction($name)) {
            throw new ProgrammingError(
                sprintf(
                    'Cannot assign the same helper function twice: "%s"',
                    $name
                )
            );
        }

        $this->helperFunctions[$name] = $function;
        return $this;
    }

    /**
     * Call a helper function
     *
     * @param  string  $name The desired function name
     * @param  Array   $args Function arguments
     * @return mixed
     */
    public function callHelperFunction($name, $args)
    {
        return call_user_func_array(
            $this->helperFunctions[$name],
            $args
        );
    }

    public function translate($text)
    {
        return Translator::translate($text, $this->translationDomain);
    }

    /**
     * Load helpers
     */
    private function loadGlobalHelpers()
    {
        $pattern = dirname(__FILE__) . '/View/helpers/*.php';
        $files = glob($pattern);
        foreach ($files as $file) {
            require_once $file;
        }
    }

    // @codingStandardsIgnoreStart
    /**
     * Use to include the view script in a scope that only allows public
     * members.
     *
     * @return mixed
     *
     * @see Zend_View_Abstract::run
     */
    protected function _run()
    {
        foreach ($this->getVars() as $k => $v) {
            // Exporting global variables to view scripts:
            $$k = $v;
        }
        if ($this->useViewStream) {
            include 'zend.view://' . func_get_arg(0);
        } else {
            include func_get_arg(0);
        }
    }
    // @codingStandardsIgnoreEnd

    /**
     * Accesses a helper object from within a script
     *
     * @param string $name
     * @param array  $args
     *
     * @return string
     */
    public function __call($name, $args)
    {
        if ($this->hasHelperFunction($name)) {
            return $this->callHelperFunction($name, $args);
        } else {
            return parent::__call($name, $args);
        }
    }
}
// @codeCoverageIgnoreEnd
