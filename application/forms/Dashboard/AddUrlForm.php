<?php
// @codeCoverageIgnoreStart
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

namespace Icinga\Form\Dashboard;

use Icinga\Application\Config as IcingaConfig;
use Icinga\Web\Form;
use Icinga\Web\Widget\Dashboard;
use Zend_Form_Element_Text;
use Zend_Form_Element_Submit;
use Zend_Form_Element_Hidden;
use Zend_Form_Element_Select;

/**
 * Form to add an url a dashboard pane
 */
class AddUrlForm extends Form
{
    /**
     * Add a selection box for different panes to the form
     *
     * @param Dashboard $dashboard      The dashboard to retrieve the panes from
     */
    private function addPaneSelectionBox(Dashboard $dashboard)
    {
        $selectPane = new Zend_Form_Element_Select(
            'pane',
            array(
                'label'     => 'Dashboard',
                'required'  => true,
                'style'     => 'display:inline-block;',

                'multiOptions' => $dashboard->getPaneKeyTitleArray()
            )
        );

        $newDashboardBtn = new Zend_Form_Element_Submit(
            'create_new_pane',
            array(
                'label'     => 'Create A New Pane',
                'required'  => false,
                'class'     => 'btn btn-default',
                'style'     => 'display:inline-block'
            )
        );

        $newDashboardBtn->removeDecorator('DtDdWrapper');
        $selectPane->removeDecorator('DtDdWrapper');
        $selectPane->removeDecorator('htmlTag');

        $this->addElement($selectPane);
        $this->addElement($newDashboardBtn);
        $this->enableAutoSubmit(array('create_new_pane'));
    }

    /**
     *  Add a textfield for creating a new pane to this form
     */
    private function addNewPaneTextField($showExistingButton = true)
    {
        $txtCreatePane = new Zend_Form_Element_Text(
            'pane',
            array(
                'label'     => 'New Dashboard Title',
                'required'  => true,
                'style'     => 'display:inline-block'
            )
        );

        // Marks this field as a new pane (and prevents the checkbox being displayed when validation errors occur)
        $markAsNewPane = new Zend_Form_Element_Hidden(
            'create_new_pane',
            array(
                'required'  => true,
                'value'     => 1
            )
        );

        $cancelDashboardBtn = new Zend_Form_Element_Submit(
            'use_existing_dashboard',
            array(
                'class'     => 'btn',
                'escape'    => false,
                'label'     => 'Use An Existing Dashboard',
                'required'  => false
            )
        );


        $cancelDashboardBtn->removeDecorator('DtDdWrapper');
        $txtCreatePane->removeDecorator('DtDdWrapper');
        $txtCreatePane->removeDecorator('htmlTag');

        $this->addElement($txtCreatePane);
        if ($showExistingButton) {
            $this->addElement($cancelDashboardBtn);
        }
        $this->addElement($markAsNewPane);
    }

    /**
     * Add elements to this form (used by extending classes)
     */
    protected function create()
    {
        $dashboard = new Dashboard();
        $this->setName('form_dashboard_add');
        $dashboard->readConfig(IcingaConfig::app('dashboard/dashboard'));
        $this->addElement(
            'text',
            'url',
            array(
                'label'    => 'Url',
                'required' => true,
                'value'    => htmlspecialchars_decode($this->getRequest()->getParam('url', ''))
            )
        );
        $elems = $dashboard->getPaneKeyTitleArray();

        if (empty($elems) ||    // show textfield instead of combobox when no pane is available
            ($this->getRequest()->getPost('create_new_pane', '0') &&  // or when a new pane should be created (+ button)
            !$this->getRequest()->getPost('use_existing_dashboard', '0')) // and the user didn't click the 'use
                                                                          // existing' button
        ) {
            $this->addNewPaneTextField(!empty($elems));
        } else {
            $this->addPaneSelectionBox($dashboard);
        }

        $this->addElement(
            'text',
            'component',
            array(
                'label'    => 'Title',
                'required' => true,
            )
        );
        $this->setSubmitLabel("Add To Dashboard");
    }
}
// @codeCoverageIgnoreEnd
