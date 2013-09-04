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

use \Zend_Form_Element_Hidden;

/**
 * Form to handle confirmations with a single value processed
 */
class CommandWithIdentifierForm extends CommandForm
{
    /**
     * Identifier for data field
     *
     * @var string
     */
    private $fieldName = 'objectid';

    /**
     * Label for the field
     *
     * Human readable sting, must be translated before.
     *
     * @var string
     */
    private $fieldLabel;

    /**
     * Setter for field label
     *
     * @param string $fieldLabel
     */
    public function setFieldLabel($fieldLabel)
    {
        $this->fieldLabel = $fieldLabel;
    }

    /**
     * Getter for field label
     *
     * @return string
     */
    public function getFieldLabel()
    {
        return $this->fieldLabel;
    }

    /**
     * Setter for field name
     *
     * @param string $fieldName
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
    }

    /**
     * Getter for field name
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Create corresponding field for object configuration
     * @return Zend_Form_Element_Hidden
     */
    private function createObjectField()
    {
        $value = $this->getRequest()->getParam($this->getFieldName());
        $fieldLabel = $this->getFieldLabel();

        $hiddenField = new Zend_Form_Element_Hidden($this->getFieldName());
        $hiddenField->setValue($value);
        $hiddenField->setRequired(true);
        $hiddenField->addValidator(
            'digits',
            true
        );

        $hiddenField->removeDecorator('Label');

        $hiddenField->addDecorator(
            'Callback',
            array(
                'callback' => function () use ($value, $fieldLabel) {
                    return sprintf(
                        '%s %s <strong>"%s"</strong>',
                        $fieldLabel,
                        t('is'),
                        (isset($value)) ? $value : t('unset')
                    );
                }
            )
        );

        return $hiddenField;
    }

    /**
     * Interface method to build the form
     * @see CommandForm::create
     */
    protected function create()
    {
        $this->addElement($this->createObjectField());
        parent::create();
    }
}
