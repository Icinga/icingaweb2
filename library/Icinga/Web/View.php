<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use ArrayIterator;
use Closure;
use Icinga\Application\Icinga;
use ipl\I18n\Translation;
use IteratorAggregate;
use Zend_View_Abstract;
use Icinga\Authentication\Auth;
use Icinga\Exception\ProgrammingError;

/**
 * Icinga view
 *
 * @method Url href($path = null, $params = null) {
 *  @param  Url|string|null $path
 *  @param  string[]|null   $params
 * }
 *
 * @method Url url($path = null, $params = null) {
 *  @param  Url|string|null $path
 *  @param  string[]|null   $params
 * }
 *
 * @method Url qlink($title, $url, $params = null, $properties = null, $escape = true) {
 *  @param  string          $title
 *  @param  Url|string|null $url
 *  @param  string[]|null   $params
 *  @param  string[]|null   $properties
 *  @param  bool            $escape
 * }
 *
 * @method string img($url, $params = null, array $properties = array()) {
 *  @param  Url|string|null $url
 *  @param  string[]|null   $params
 *  @param  string[]        $properties
 * }
 *
 * @method string icon($img, $title = null, array $properties = array()) {
 *  @param  string      $img
 *  @param  string|null $title
 *  @param  string[]    $properties
 * }
 *
 * @method string propertiesToString($properties) {
 *  @param  string[]    $properties
 * }
 *
 * @method string attributeToString($key, $value) {
 *  @param  string  $key
 *  @param  string  $value
 * }
 */
class View extends Zend_View_Abstract implements IteratorAggregate
{
    use Translation;

    /**
     * Charset to be used - we only support UTF-8
     */
    const CHARSET = 'UTF-8';

    /**
     * @var array
     */
    private $dynamicProperties = [];

    /**
     * Registered helper functions
     */
    private $helperFunctions = array();

    /**
     * Authentication manager
     *
     * @var Auth|null
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
        $config['helperPath']['Icinga\\Web\\View\\Helper\\'] = Icinga::app()->getLibraryDir('Icinga/Web/View/Helper');

        parent::__construct($config);
    }

    public function __set($key, $val): void
    {
        if ('_' != substr($key, 0, 1)) {
            $this->dynamicProperties[$key] = $val;

            return;
        }

        // trigger exception
        parent::__set($key, $val);
    }

    public function __get($key)
    {
        return array_key_exists($key, $this->dynamicProperties)
            ? $this->dynamicProperties[$key]
            : parent::__get($key); // trigger error
    }

    public function __isset($key): bool
    {
        return isset($this->dynamicProperties[$key]);
    }

    public function __unset($key): void
    {
        if ('_' != substr($key, 0, 1) && isset($this->dynamicProperties[$key])) {
            unset($this->dynamicProperties[$key]);
        }
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->dynamicProperties);
    }

    public function assign($spec, $value = null): self
    {
        if (is_string($spec)) {
            if ('_' == substr($spec, 0, 1)) {
                // trigger exception
                return parent::assign($spec, $value);
            }

            $this->dynamicProperties[$spec] = $value;
        } elseif (is_array($spec)) {
            foreach ($spec as $key => $val) {
                if ('_' == substr($key, 0, 1)) {
                    // trigger exception
                    return parent::assign($spec, $value);
                }

                $this->dynamicProperties[$key] = $val;
                unset($spec[$key]);
            }
        } else {
            // trigger exception
            return parent::assign($spec, $value);
        }

        return $this;
    }

    public function getVars(): array
    {
        return $this->dynamicProperties;
    }

    public function clearVars(): void
    {
        $this->dynamicProperties = [];
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
        return htmlspecialchars($value ?? '', ENT_COMPAT | ENT_SUBSTITUTE | ENT_HTML5, self::CHARSET, true);
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
     * Set or overwrite a helper function
     *
     * @param   string  $name
     * @param   Closure $function
     *
     * @return  $this
     */
    public function setHelperFunction($name, Closure $function)
    {
        $this->helperFunctions[$name] = $function;
        return $this;
    }

    /**
     * Drop a helper function
     *
     * @param   string  $name
     *
     * @return  $this
     */
    public function dropHelperFunction($name)
    {
        unset($this->helperFunctions[$name]);
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
     * @return Auth
     */
    public function Auth()
    {
        if ($this->auth === null) {
            $this->auth = Auth::getInstance();
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

        include func_get_arg(0);
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
