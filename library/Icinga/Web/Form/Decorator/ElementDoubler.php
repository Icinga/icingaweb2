<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Decorator;

use Zend_Form_Element;
use Zend_Form_Decorator_Abstract;

/**
 * A decorator that will double a single element of a display group
 *
 * The options `condition', `double' and `attributes' can be passed to the constructor and are used to affect whether
 * the doubling should take effect, which element should be doubled and which HTML attributes should be applied to the
 * doubled element, respectively.
 *
 * `condition' must be an element's name that when it's part of the display group causes the condition to be met.
 * `double' must be an element's name and must be part of the display group.
 * `attributes' is just an array of key-value pairs.
 *
 * You can also pass `placement' to control whether the doubled element is prepended or appended.
 */
class ElementDoubler extends Zend_Form_Decorator_Abstract
{
    /**
     * Return the display group's elements with an additional copy of an element being added if the condition is met
     *
     * @param   string  $content    The HTML rendered so far
     *
     * @return  string
     */
    public function render($content)
    {
        $group = $this->getElement();
        if ($group->getElement($this->getOption('condition')) !== null) {
            if ($this->getPlacement() === static::APPEND) {
                return $content . $this->applyAttributes($group->getElement($this->getOption('double')))->render();
            } else { // $this->getPlacement() === static::PREPEND
                return $this->applyAttributes($group->getElement($this->getOption('double')))->render() . $content;
            }
        }

        return $content;
    }

    /**
     * Apply all element attributes
     *
     * @param   Zend_Form_Element   $element    The element to apply the attributes to
     *
     * @return  Zend_Form_Element
     */
    protected function applyAttributes(Zend_Form_Element $element)
    {
        $attributes = $this->getOption('attributes');
        if ($attributes !== null) {
            foreach ($attributes as $name => $value) {
                $element->setAttrib($name, $value);
            }
        }

        return $element;
    }
}
