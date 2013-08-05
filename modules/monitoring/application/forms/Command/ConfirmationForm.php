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

namespace Monitoring\Form\Command;

use Icinga\Web\Form;
use Icinga\Web\Form\Element\Note;
use \Zend_Form_Element_Hidden;
use \Zend_Validate_Date;

/**
 * Simple confirmation command
 */
class ConfirmationForm extends Form
{
    /**
     * Default date format
     */
    const DEFAULT_DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * Default format for validation
     */
    const DEFAULT_DATE_VALIDATION = 'yyyy-MM-dd hh:ii:ss';

    /**
     * Array of messages
     * 
     * @var string[]
     */
    private $notes = array();

    /**
     * Add message to stack
     *
     * @param string $message
     */
    public function addNote($message)
    {
        $this->notes[] = $message;
    }

    /**
     * Purge messages from stack
     *
     */
    public function clearNotes()
    {
        $this->notes = array();
    }

    /**
     * Getter for notes
     *
     * @return string[]
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Create an instance name containing hidden field
     * @return Zend_Form_Element_Hidden
     */
    private function createInstanceHiddenField()
    {
        $field = new Zend_Form_Element_Hidden('instance');
        $value = $this->getRequest()->getParam($field->getName());
        $field->setValue($value);
        return $field;
    }

    /**
     * Add elements to this form (used by extending classes)
     *
     * @see Form::create
     */
    protected function create()
    {
        if (count($this->getNotes())) {
            foreach ($this->getNotes() as $nodeid => $note) {
                $element = new Note(
                    array(
                        'name' => 'note_'. $nodeid,
                        'value' => $note
                    )
                );
                $this->addElement($element);
            }
        }

        $this->addElement($this->createInstanceHiddenField());
    }

    /**
     * Get the author name

     */
    protected function getAuthorName()
    {
        if (is_a($this->getRequest(), "Zend_Controller_Request_HttpTestCase")) {
            return "Test user";
        }
        return $this->getRequest()->getUser()->getUsername();
    }

    /**
     * Creator for author field
     *
     * @return Zend_Form_Element_Hidden
     */
    protected function createAuthorField()
    {
        $authorName = $this->getAuthorName();

        $authorField = new Zend_Form_Element_Hidden(
            array(
                'name'       => 'author',
                'label'      => t('Author name'),
                'value'      => $authorName,
                'required'   => true
            )
        );

        $authorField->addDecorator(
            'Callback',
            array(
                'callback' => function () use ($authorName) {
                    return sprintf('<strong>%s</strong>', $authorName);
                }
            )
        );

        return $authorField;
    }

    /**
     * Getter for date format
     * TODO(mh): Should be user preferences
     * @return string
     */
    protected function getDateFormat()
    {
        return self::DEFAULT_DATE_FORMAT;
    }

    /**
     * Getter for date validation format
     * @return string
     */
    protected function getDateValidationFormat()
    {
        return self::DEFAULT_DATE_VALIDATION;
    }

    /**
     * Create a new date validator
     * @return Zend_Validate_Date
     */
    protected function createDateTimeValidator()
    {
        $validator = new Zend_Validate_Date();
        $validator->setFormat($this->getDateValidationFormat());
        return $validator;
    }
}
