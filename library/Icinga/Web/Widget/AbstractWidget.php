<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Exception\ProgrammingError;
use Icinga\Application\Icinga;
use Exception;

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
     * its instance is stored here for performance reasons.
     *
     * @var Zend_View_Abstract
     */
    protected static $view;

    // TODO: Should we kick this?
    protected $properties = array();

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
            'Trying to get invalid "%s" property for %s',
            $key,
            get_class($this)
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
            'Trying to set invalid "%s" property in %s. Allowed are: %s',
            $key,
            get_class($this),
            empty($this->properties)
                ? 'none'
                : implode(', ', array_keys($this->properties))
        );
    }

    abstract public function render();

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
            self::$view = Icinga::app()->getViewRenderer()->view;
        }

        return self::$view;
    }

    /**
     * Cast this widget to a string. Will call your render() function
     *
     * @return string
     */
    public function __toString()
    {
        try {
            $html = $this->render($this->view());
        } catch (Exception $e) {
            return htmlspecialchars($e->getMessage());
        }
        return (string) $html;
    }
}
