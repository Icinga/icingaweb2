<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Closure;
use Zend_View_Abstract;
use Icinga\Authentication\Manager;
use Icinga\Exception\ProgrammingError;
use Icinga\Util\Translator;

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
     * Authentication manager
     *
     * @var \Icinga\Authentication\Manager|null
     */
    private $auth;

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
     * @return $this
     */
    public function addHelperFunction($name, Closure $function)
    {
        if ($this->hasHelperFunction($name)) {
            throw new ProgrammingError(
                'Cannot assign the same helper function twice: "%s"',
                $name
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

    public function translate($text, $context = null)
    {
        return Translator::translate($text, $this->translationDomain, $context);
    }

    /**
     * Translate a plural string
     *
     * @see Translator::translatePlural()
     */
    public function translatePlural($textSingular, $textPlural, $number, $context = null)
    {
        return Translator::translatePlural($textSingular, $textPlural, $number, $this->translationDomain, $context);
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

    /**
     * Get the authentication manager
     *
     * @return Manager
     */
    public function Auth()
    {
        if ($this->auth === null) {
            $this->auth = Manager::getInstance();
        }
        return $this->auth;
    }

    /**
     * Whether the current user has the given permission
     *
     * @param   string  $permission Name of the permission
     *
     * @return  bool
     */
    public function hasPermission($permission)
    {
        return $this->Auth()->hasPermission($permission);
    }

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
