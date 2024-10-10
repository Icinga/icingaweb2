<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Closure;
use Icinga\Application\Icinga;
use ipl\I18n\Translation;
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
class View extends Zend_View_Abstract
{
    use Translation;

    /**
     * Charset to be used - we only support UTF-8
     */
    const CHARSET = 'UTF-8';

    /**
     * Legacy icons provided by our old fontello font
     *
     * @var array<string, bool>
     */
    private const LEGACY_ICONS = [
        //<editor-fold desc="Icon names">
        'dashboard' => true,
        'user' => true,
        'users' => true,
        'ok' => true,
        'cancel' => true,
        'plus' => true,
        'minus' => true,
        'folder-empty' => true,
        'download' => true,
        'upload' => true,
        'git' => true,
        'cubes' => true,
        'database' => true,
        'gauge' => true,
        'sitemap' => true,
        'sort-name-up' => true,
        'sort-name-down' => true,
        'megaphone' => true,
        'bug' => true,
        'tasks' => true,
        'filter' => true,
        'off' => true,
        'book' => true,
        'paste' => true,
        'scissors' => true,
        'globe' => true,
        'cloud' => true,
        'flash' => true,
        'barchart' => true,
        'down-dir' => true,
        'up-dir' => true,
        'left-dir' => true,
        'right-dir' => true,
        'down-open' => true,
        'right-open' => true,
        'up-open' => true,
        'left-open' => true,
        'up-big' => true,
        'right-big' => true,
        'left-big' => true,
        'down-big' => true,
        'resize-full-alt' => true,
        'resize-full' => true,
        'resize-small' => true,
        'move' => true,
        'resize-horizontal' => true,
        'resize-vertical' => true,
        'zoom-in' => true,
        'block' => true,
        'zoom-out' => true,
        'lightbulb' => true,
        'clock' => true,
        'volume-up' => true,
        'volume-down' => true,
        'volume-off' => true,
        'mute' => true,
        'mic' => true,
        'endtime' => true,
        'starttime' => true,
        'calendar-empty' => true,
        'calendar' => true,
        'wrench' => true,
        'sliders' => true,
        'services' => true,
        'service' => true,
        'phone' => true,
        'file-pdf' => true,
        'file-word' => true,
        'file-excel' => true,
        'doc-text' => true,
        'trash' => true,
        'comment-empty' => true,
        'comment' => true,
        'chat' => true,
        'chat-empty' => true,
        'bell' => true,
        'bell-alt' => true,
        'attention-alt' => true,
        'print' => true,
        'edit' => true,
        'forward' => true,
        'reply' => true,
        'reply-all' => true,
        'eye' => true,
        'tag' => true,
        'tags' => true,
        'lock-open-alt' => true,
        'lock-open' => true,
        'lock' => true,
        'home' => true,
        'info' => true,
        'help' => true,
        'search' => true,
        'flapping' => true,
        'rewind' => true,
        'chart-line' => true,
        'bell-off' => true,
        'bell-off-empty' => true,
        'plug' => true,
        'eye-off' => true,
        'arrows-cw' => true,
        'cw' => true,
        'host' => true,
        'thumbs-up' => true,
        'thumbs-down' => true,
        'spinner' => true,
        'attach' => true,
        'keyboard' => true,
        'menu' => true,
        'wifi' => true,
        'moon' => true,
        'chart-pie' => true,
        'chart-area' => true,
        'chart-bar' => true,
        'beaker' => true,
        'magic' => true,
        'spin6' => true,
        'down-small' => true,
        'left-small' => true,
        'right-small' => true,
        'up-small' => true,
        'pin' => true,
        'angle-double-left' => true,
        'angle-double-right' => true,
        'circle' => true,
        'info-circled' => true,
        'twitter' => true,
        'facebook-squared' => true,
        'gplus-squared' => true,
        'attention-circled' => true,
        'check' => true,
        'reschedule' => true,
        'warning-empty' => true,
        'th-list' => true,
        'th-thumb-empty' => true,
        'github-circled' => true,
        'history' => true,
        'binoculars' => true
        //</editor-fold>
    ];

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
     * @param  ?string $var  The output to be escaped
     * @return string
     */
    public function escape($var)
    {
        return htmlspecialchars($var ?? '', ENT_COMPAT | ENT_SUBSTITUTE | ENT_HTML5, self::CHARSET, true);
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
