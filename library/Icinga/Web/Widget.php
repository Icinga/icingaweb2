<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web;

use Icinga\Exception\ProgrammingError;

/**
 * Web widgets make things easier for you!
 *
 * This class provides nothing but a static factory method for widget creation.
 * Usually it will not be used directly as there are widget()-helpers available
 * in your action controllers and view scripts.
 *
 * Usage example:
 * <code>
 * $tabs = Widget::create('tabs');
 * </code>
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class Widget
{
    /**
     * Create a new widget
     *
     * @param string $name    Widget name
     * @param array  $options Widget constructor options
     *
     * @return Icinga\Web\Widget\AbstractWidget
     */
    public static function create($name, $options = array(), $module_name = null)
    {
        $class = 'Icinga\\Web\\Widget\\' . ucfirst($name);

        if (! class_exists($class)) {
            throw new ProgrammingError(
                sprintf(
                    'There is no such widget: %s',
                    $name
                )
            );
        }

        $widget = new $class($options, $module_name);
        return $widget;
    }
}
