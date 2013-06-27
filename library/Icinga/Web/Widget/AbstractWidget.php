<?php

/**
 * Web Widget abstract class
 */
namespace Icinga\Web\Widget;

use Icinga\Exception\ProgrammingError;
use Zend_Controller_Action_HelperBroker as ZfActionHelper;

/**
 * Web widgets MUST extend this class
 *
 * AbstractWidget implements getters and setters for widget options stored in
 * the protected options array. If you want to allow options for your own
 * widget, you have to set a default value (may be null) for each single option
 * in this array.
 *
 * Please have a look at the available widgets in this folder to get a better
 * idea on what they should look like.
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
abstract class AbstractWidget
{
    /**
     * If you are going to access the current view with the view() function,
     * it's instance is stored here for performance reasons.
     *
     * @var Zend_View_Abstract
     */
    protected static $view;

    protected $module_name;

    /**
     * Fill $properties with default values for all your valid widget properties
     *
     * @var array
     */
    protected $properties = array();

    /**
     * You MUST extend this function. This is where all your HTML voodoo happens
     *
     * @return string
     */
    abstract public function renderAsHtml();

    /**
     * You are not allowed to override the constructor, but you can put
     * initialization stuff in your init() function
     *
     * @return void
     */
    protected function init()
    {
    }

    /**
     * We are not allowing you to override the constructor unless someone
     * presents a very good reason for doing so
     *
     * @param array $properties An optional properties array
     */
    final public function __construct($properties = array(), $module_name = null)
    {
        if ($module_name !== null) {
            $this->module_name = $module_name;
        }
        foreach ($properties as $key => $val) {
            $this->$key = $val;
        }
        $this->init();
    }

    /**
     * Getter for widget properties
     *
     * @param  string $key The option you're interested in
     *
     * @throws ProgrammingError for unknown property name
     *
     * @return mixed
     */
    public function __get($key)
    {
        if (array_key_exists($key, $this->properties)) {
            return $this->properties[$key];
        }

        throw new ProgrammingError(
            sprintf(
                'Trying to get invalid "%s" property for %s',
                $key,
                get_class($this)
            )
        );
    }

    /**
     * Setter for widget properties
     *
     * @param  string $key The option you want to set
     * @param  string $val The new value going to be assigned to this option
     *
     * @throws ProgrammingError for unknown property name
     *
     * @return mixed
     */
    public function __set($key, $val)
    {
        if (array_key_exists($key, $this->properties)) {
            $this->properties[$key] = $val;
            return;
        }

        throw new ProgrammingError(
            sprintf(
                'Trying to set invalid "%s" property in %s. Allowed are: %s',
                $key,
                get_class($this),
                empty($this->properties)
                ? 'none'
                : implode(', ', array_keys($this->properties))
            )
        );
    }

    /**
     * Access the current view
     *
     * Will instantiate a new one if none exists
     * // TODO: App->getView
     *
     * @return Zend_View_Abstract
     */
    protected function view()
    {
        if (self::$view === null) {

            $renderer = ZfActionHelper::getStaticHelper(
                'viewRenderer'
            );

            if (null === $renderer->view) {
                $renderer->initView();
            }

            self::$view = $renderer->view;
        }

        return self::$view;
    }

    /**
     * Cast this widget to a string. Will call your renderAsHtml() function
     *
     * @return string
     */
    public function __toString()
    {
        return $this->renderAsHtml();
    }
}
