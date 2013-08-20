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

use \Zend_View_Abstract;
use \Icinga\Web\Url;
use \Icinga\Util\Format;

/**
 * Icinga view
 */
class View extends Zend_View_Abstract
{
    /**
     * Flag to register stream wrapper
     *
     * @var bool
     */
    private $useViewStream = false;

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
        $namespaced = '\\Icinga\\Web\\View\\' . $name;
        if (function_exists($namespaced)) {
            return call_user_func_array(
                $namespaced,
                $args
            );
        } else {
            return parent::__call($name, $args);
        }
    }
}
