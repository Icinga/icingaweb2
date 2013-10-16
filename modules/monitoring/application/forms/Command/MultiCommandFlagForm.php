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

namespace Icinga\Module\Monitoring\Form\Command;

use \Icinga\Web\Form\Element\TriStateCheckbox;
use \Icinga\Web\Form;
use \Zend_Form_Element_Hidden;

/**
 * A form to edit multiple command flags of multiple commands at once. When some commands have
 * different flag values, these flags will be displayed as an undefined state,
 * in a tri-state checkbox.
 */
class MultiCommandFlagForm extends Form {

    /**
     * The Suffix used to mark the old values in the form-field
     *
     * @var string
     */
    const OLD_VALUE_MARKER = '_&&old';

    /**
     * The object properties to change
     *
     * @var array
     */
    private $flags;

    /**
     * The current values of this form
     *
     * @var array
     */
    private $values;

    /**
     * Create a new MultiCommandFlagForm
     *
     * @param array $flags  The flags that will be used. Should contain the
     *                      names of the used property keys.
     */
    public function __construct(array $flags)
    {
        $this->flags = $flags;
        parent::__construct();
    }

    /**
     * Initialise the form values with the array of items to configure.
     *
     * @param array $items  The items that will be edited with this form.
     */
    public function initFromItems(array $items)
    {
        $this->values = $this->valuesFromObjects($items);
        $this->buildForm();
        $this->populate($this->values);
    }

    /**
     * Return only the values that have been updated.
     */
    public function getChangedValues()
    {
        $values = $this->getValues();
        $changed = array();
        foreach ($values as $key => $value) {
            $oldKey = $key . self::OLD_VALUE_MARKER;
            if (array_key_exists($oldKey, $values)) {
                if ($values[$oldKey] !== $value) {
                    $changed[$key] = $value;
                }
            }
        }
        return $changed;
    }

    /**
     * Extract the values from a set of items.
     *
     * @param array $items  The items
     */
    private function valuesFromObjects(array $items)
    {
        $values = array();
        foreach ($items as $item) {
            foreach ($this->flags as $key => $unused) {

                if (isset($item->{$key})) {
                    $value = $item->{$key};

                    // convert strings
                    if ($value === '1' || $value === '0') {
                        $value = intval($value);
                    }

                    // init key with first value
                    if (!array_key_exists($key, $values)) {
                        $values[$key] = $value;
                        continue;
                    }

                    // already a mixed state ?
                    if ($values[$key] === 'unchanged') {
                        continue;
                    }

                    // values differ?
                    if ($values[$key] ^ $value) {
                        $values[$key] = 'unchanged';
                    }
                }
            }
        }
        $old = array();
        foreach ($values as $key => $value) {
            $old[$key . self::OLD_VALUE_MARKER] = $key;
        }
        return array_merge($values, $old);
    }

    /**
     * Create the multi flag form
     *
     * @see Form::create()
     */
    public function create()
    {
        $this->setName('form_flag_configuration');
        foreach ($this->flags as $flag => $description) {
            $this->addElement(new TriStateCheckbox(
                $flag,
                array(
                    'label' => $description,
                    'required' => true
                )
            ));

            $old = new Zend_Form_Element_Hidden($flag . self::OLD_VALUE_MARKER);
            $this->addElement($old);
        }
        $this->setSubmitLabel('Save Configuration');
    }
}
