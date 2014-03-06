<?php
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

namespace Icinga\Form\Config\Authentication;

use \Zend_Config;
use \Icinga\Web\Form;
use \Icinga\Web\Url;

/**
 * Form for modifying the authentication provider order.
 *
 */
class ReorderForm extends Form
{
    /**
     * The name of the current backend which will get action buttons for up and down movement
     *
     * @var string
     */
    private $backend = null;

    /**
     * The current ordering of all backends, required to determine possible changes
     *
     * @var array
     */
    private $currentOrder = array();

    /**
     * Set an array with the current order of all backends
     *
     * @param array $order  An array containing backend names in the order they are defined in the authentication.ini
     */
    public function setCurrentOrder(array $order)
    {
        $this->currentOrder = $order;
    }

    /**
     * Set the name of the authentication backend for which to create the form
     *
     * @param string $backend   The name of the authentication backend
     */
    public function setAuthenticationBackend($backend)
    {
        $this->backend = $backend;
    }

    /**
     * Return the name of the currently set backend as it will appear in the forms
     *
     * This calls the Zend Filtername function in order to filter specific chars
     *
     * @return string       The filtered name of the backend
     * @see Form::filterName()
     */
    public function getBackendName()
    {
        return $this->filterName($this->backend);
    }

    /**
     * Create this form.
     *
     * Note: The form action will be set here to the authentication overview
     *
     * @see Form::create
     */
    public function create()
    {
        $this->upForm = new Form();
        $this->downForm = new Form();

        if ($this->moveElementUp($this->backend, $this->currentOrder) !== $this->currentOrder) {

            $this->upForm->addElement(
                'hidden',
                'form_backend_order',
                array(
                    'required'  => true,
                    'value'     =>  join(',', $this->moveElementUp($this->backend, $this->currentOrder))
                )
            );
            $this->upForm->addElement(
                'button',
                'btn_' . $this->getBackendName() . '_reorder_up',
                array(
                    'type'      => 'submit',
                    'escape'    => false,
                    'value'     => 'btn_' . $this->getBackendName() . '_reorder_up',
                    'name'      =>  'btn_' . $this->getBackendName() . '_reorder_up',
                    'label'     =>  '<img src="/icingaweb/img/icons/up.png" title="Move up in authentication order" />',
                )
            );
        }

        if ($this->moveElementDown($this->backend, $this->currentOrder) !== $this->currentOrder) {
            $this->downForm->addElement(
                'hidden',
                'form_backend_order',
                array(
                    'required'  => true,
                    'value'     =>  join(',', $this->moveElementDown($this->backend, $this->currentOrder))
                )
            );
            $this->downForm->addElement(
                'button',
                'btn_' . $this->getBackendName() . '_reorder_down',
                array(
                    'type'      => 'submit',
                    'escape'    => false,
                    'value'     => 'btn_' . $this->getBackendName() . '_reorder_down',
                    'name'      =>  'btn_' . $this->getBackendName() . '_reorder_down',
                    'label'     =>  '<img src="/icingaweb/img/icons/down.png" title="Move down in authentication order" />',

                )
            );
        }
        $this->setAction(Url::fromPath("config/authentication", array())->getAbsoluteUrl());
    }

    /**
     * Return the result of $this->getValues but flatten the result
     *
     * The result will be a key=>value array without subarrays
     *
     * @param bool $supressArrayNotation        passed to getValues
     *
     * @return array                            The currently set values
     * @see Form::getValues()
     */
    public function getFlattenedValues($supressArrayNotation = false)
    {
        $values = parent::getValues($supressArrayNotation);
        $result = array();
        foreach ($values as $key => &$value) {
            if (is_array($value)) {
                $result += $value;
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Determine whether this form is submitted by testing the submit buttons of both subforms
     *
     * @return bool     True when the form is submitted, otherwise false
     */
    public function isSubmitted()
    {
        $checkData = $this->getRequest()->getParams();
        return isset ($checkData['btn_' . $this->getBackendName() . '_reorder_up']) ||
                isset ($checkData['btn_' . $this->getBackendName() . '_reorder_down']);
    }

    /**
     * Return the reordered configuration after a reorder button has been submited
     *
     * @param Zend_Config $config       The configuration to reorder
     *
     * @return array                    An array containing the reordered configuration
     */
    public function getReorderedConfig(Zend_Config $config)
    {
        $originalConfig = $config->toArray();
        $reordered = array();
        $newOrder = $this->getFlattenedValues();
        $order = explode(',', $newOrder['form_backend_order']);
        foreach ($order as $key) {
            if (!isset($originalConfig[$key])) {
                continue;
            }
            $reordered[$key] = $originalConfig[$key];
        }

        return $reordered;

    }

    /**
     * Static helper for moving an element in an array one slot up, if possible
     *
     * Example:
     *
     * <pre>
     * $array = array('first', 'second', 'third');
     * moveElementUp('third', $array); // returns ['first', 'third', 'second']
     * </pre>
     *
     * @param   string    $key              The key to bubble up one slot
     * @param   array     $array            The array to work with
     *
     * @return  array                       The modified array
     */
    private static function moveElementUp($key, array $array)
    {
        $swap = null;
        for ($i=0; $i<count($array)-1; $i++) {
            if ($array[$i+1] !== $key) {
                continue;
            }
            $swap = $array[$i];
            $array[$i] = $array[$i+1];
            $array[$i+1] = $swap;
            return $array;
        }
        return $array;
    }

    /**
     * Static helper for moving an element in an array one slot up, if possible
     *
     * Example:
     *
     * <pre>
     * $array = array('first', 'second', 'third');
     * moveElementDown('first', $array); // returns ['second', 'first', 'third']
     * </pre>
     *
     * @param   string    $key              The key to bubble up one slot
     * @param   array     $array            The array to work with
     *
     * @return  array                       The modified array
     */
    private static function moveElementDown($key, array $array)
    {
        $swap = null;
        for ($i=0; $i<count($array)-1; $i++) {
            if ($array[$i] !== $key) {
                continue;
            }
            $swap = $array[$i+1];
            $array[$i+1] = $array[$i];
            $array[$i] = $swap;
            return $array;
        }
        return $array;
    }
}
