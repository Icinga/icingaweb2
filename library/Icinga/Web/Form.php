<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * Icinga 2 Web - Head for multiple monitoring frontends
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
 * @author Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web;

use \Zend_Controller_Request_Abstract;
use \Zend_Form;
use \Zend_Config;
use \Zend_Form_Element_Submit;
use \Zend_Form_Element_Reset;
use \Zend_View_Interface;
use \Icinga\Web\Form\Element\Note;
use \Icinga\Exception\ProgrammingError;
use \Icinga\Web\Form\Decorator\HelpText;
use \Icinga\Web\Form\InvalidCSRFTokenException;
use \Icinga\Application\Config as IcingaConfig;

/**
 * Base class for forms providing CSRF protection, confirmation logic and auto submission
 */
class Form extends Zend_Form
{
    /**
     * The form's request object
     *
     * @var Zend_Controller_Request_Abstract
     */
    private $request;

    /**
     * Main configuration
     *
     * Used as fallback if user preferences are not available.
     *
     * @var IcingaConfig
     */
    private $config;

    /**
     * The preference object to use instead of the one from the user (used for testing)
     *
     * @var Zend_Config
     */
    private $preferences;

    /**
     * Whether this form should NOT add random generated "challenge" tokens that are associated with the user's current
     * session in order to prevent Cross-Site Request Forgery (CSRF). It is the form's responsibility to verify the
     * existence and correctness of this token
     *
     * @var bool
     */
    protected $tokenDisabled = false;

    /**
     * Name of the CSRF token element (used to create non-colliding hashes)
     *
     * @var string
     */
    private $tokenElementName = 'CSRFToken';

    /**
     * Flag to indicate that form is already build
     *
     * @var bool
     */
    private $created = false;

    /**
     * Session id used for CSRF token generation
     *
     * @var string
     */
    private $sessionId;

    /**
     * Label for submit button
     *
     * If omitted, no button will be shown
     *
     * @var string
     */
    private $submitLabel;

    /**
     * Label for cancel button
     *
     * If omitted, no button will be shown
     *
     * @var string
     */
    private $cancelLabel;

    /**
     * Last used note-id
     *
     * Helper to generate unique names for note elements
     *
     * @var int
     */
    private $last_note_id = 0;

    /**
     * Getter for the session ID
     *
     * If the ID has never been set, the ID from session_id() is returned
     *
     * @return  string
     *
     * @see     session_id()
     * @see     setSessionId()
     */
    public function getSessionId()
    {
        if (!$this->sessionId) {
            $this->sessionId = session_id();
        }
        return $this->sessionId;
    }

    /**
     * Setter for the session ID
     *
     * This method should be used for testing purposes only
     *
     * @param string $sessionId
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * Return the HTML element name of the CSRF token field
     *
     * @return string
     */
    public function getTokenElementName()
    {
        return $this->tokenElementName;
    }

    /**
     * Render the form to HTML
     *
     * @param   Zend_View_Interface $view
     *
     * @return  string
     */
    public function render(Zend_View_Interface $view = null)
    {
        // Elements must be there to render the form
        $this->buildForm();
        return parent::render($view);
    }

    /**
     * Add elements to this form (used by extending classes)
     */
    protected function create()
    {
    }

    /**
     * Method called before validation
     */
    protected function preValidation(array $data)
    {
    }

    /**
     * Setter for the request
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function setRequest(Zend_Controller_Request_Abstract $request)
    {
        $this->request = $request;
    }

    /**
     * Getter for the request
     *
     * @return Zend_Controller_Request_Abstract
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set the configuration to be used for this form when no preferences are set yet
     *
     * @param IcingaConfig $cfg
     */
    public function setConfiguration($cfg)
    {
        $this->config = $cfg;
    }

    /**
     * Get the main configuration
     *
     * Returns the set configuration or an empty default one.
     *
     * @return Zend_Config
     */
    public function getConfiguration()
    {
        if ($this->config === null) {
            $this->config = new Zend_Config(array());
        }
        return $this->config;
    }

    /**
     * Set preferences to be used instead of the one from the user object (used for testing)
     *
     * @param Zend_Config $prefs
     */
    public function setUserPreferences($prefs)
    {
        $this->preferences = $prefs;
    }

    /**
     * Return the preferences of the user or the overwritten ones
     *
     * @return Zend_Config
     */
    public function getUserPreferences()
    {
        if ($this->preferences) {
            return $this->preferences;
        }
        return $this->getRequest()->getUser()->getPreferences();
    }

    /**
     * Create the form if not done already
     *
     * Adds all elements to the form
     */
    public function buildForm()
    {

        if ($this->created === false) {
            $this->initCsrfToken();
            $this->create();

            if ($this->submitLabel) {
                $this->addSubmitButton();
            }

            if ($this->cancelLabel) {
                $this->addCancelButton();
            }

            // Empty action if not safe
            if (!$this->getAction() && $this->getRequest()) {
                $this->setAction($this->getRequest()->getRequestUri());
            }
            $this->addElementDecorators();
            $this->created = true;
            $this->setAttrib('data-icinga-component', 'app/form');

        }
    }

    /**
     * Setter for the cancel label
     *
     * @param string $cancelLabel
     */
    public function setCancelLabel($cancelLabel)
    {
        $this->cancelLabel = $cancelLabel;
    }

    /**
     * Add cancel button to form
     */
    private function addCancelButton()
    {
        $cancelLabel = new Zend_Form_Element_Reset(
            array(
                'name' => 'btn_reset',
                'label' => $this->cancelLabel,
                'class' => 'btn pull-right'
            )
        );
        $this->addElement($cancelLabel);
    }

    /**
     * Setter for the submit label
     *
     * @param string $submitLabel
     */
    public function setSubmitLabel($submitLabel)
    {
        $this->submitLabel = $submitLabel;
    }

    /**
     * Add submit button to form
     */
    private function addSubmitButton()
    {
        $submitButton = new Zend_Form_Element_Submit(
            array(
                'name'  => 'btn_submit',
                'label' => $this->submitLabel,
                'class' => 'btn btn-primary pull-right'
            )
        );
        $this->addElement($submitButton);
    }

    /**
     * Add message to form
     *
     * @param string    $message        The message to be displayed
     * @param int       $headingType    Whether it should be displayed as heading (1-6) or not (null)
     */
    public function addNote($message, $headingType = null)
    {
        $this->addElement(
            new Note(
                array(
                    'escape'    => $headingType === null ? false : true,
                    'name'      => sprintf('note_%s', $this->last_note_id++),
                    'value'     => $headingType === null ? $message : sprintf(
                        '<h%1$s>%2$s</h%1$s>',
                        $headingType,
                        $message
                    )
                )
            )
        );
    }

    /**
     * Enable automatic form submission on the given elements
     *
     * Enables automatic submission of this form once the user edits specific elements
     *
     * @param   array $triggerElements  The element names which should auto-submit the form
     *
     * @throws  ProgrammingError        When an element is found which does not yet exist
     */
    final public function enableAutoSubmit($triggerElements)
    {
        foreach ($triggerElements as $elementName) {
            $element = $this->getElement($elementName);
            if ($element !== null) {
                $element->setAttrib('data-icinga-form-autosubmit', 'true');
            } else {
                throw new ProgrammingError(
                    'You need to add the element "' . $elementName . '" to' .
                    ' the form before automatic submission can be enabled!'
                );
            }
        }
    }

    /**
     * Check whether the form was submitted with a valid request
     *
     * Ensures that the current request method is POST, that the form was manually submitted and that the data provided
     * in the request is valid and gets repopulated in case its invalid.
     *
     * @return  bool                True when the form is submitted and valid, otherwise false
     * @see     Form::isValid()
     * @see     Form::isSubmitted()
     */
    public function isSubmittedAndValid()
    {
        if ($this->getRequest()->isPost() === false) {
            return false;
        }

        $this->buildForm();
        $checkData = $this->getRequest()->getParams();
        $this->assertValidCsrfToken($checkData);

        if ($this->isSubmitted()) {
            // perform full validation if submitted
            $this->preValidation($checkData);
            return $this->isValid($checkData);
        } else {
            // only populate if not submitted
            $this->populate($checkData);
            $this->setAttrib('data-icinga-form-modified', 'true');
            return false;
        }
    }


    /**
     * Check whether this form has been submitted
     *
     * Per default, this checks whether the button set with the 'setSubmitLabel' method
     * is being submitted. For custom submission logic, this method must be overwritten
     *
     * @return bool True when the form is marked as submitted, otherwise false
     */
    public function isSubmitted()
    {
        $submitted = true;
        if ($this->submitLabel) {
            $checkData = $this->getRequest()->getParams();
            $submitted = isset($checkData['btn_submit']);
        }
        return $submitted;
    }

    /**
     * Disable CSRF counter measure and remove its field if already added
     *
     * This method should be used for testing purposes only
     *
     * @param   bool $disabled  Set true in order to disable CSRF tokens in this form (default: true), otherwise false
     *
     * @see     tokenDisabled
     */
    final public function setTokenDisabled($disabled = true)
    {
        $this->tokenDisabled = (boolean) $disabled;
        if ($disabled === true) {
            $this->removeElement($this->tokenElementName);
        }
    }

    /**
     * Add CSRF counter measure field to form
     */
    final public function initCsrfToken()
    {
        if ($this->tokenDisabled || $this->getElement($this->tokenElementName)) {
            return;
        }
        $this->addElement(
            'hidden',
            $this->tokenElementName,
            array(
                'value'         => $this->generateCsrfTokenAsString(),
                'decorators'    => array('ViewHelper')
            )
        );
    }

    /**
     * Test the submitted data for a correct CSRF token
     *
     * @param   array $checkData            The POST data send by the user
     *
     * @throws  InvalidCSRFTokenException   When CSRF Validation fails
     */
    final public function assertValidCsrfToken(array $checkData)
    {
        if ($this->tokenDisabled) {
            return;
        }

        if (!isset($checkData[$this->tokenElementName])
            || !$this->hasValidCsrfToken($checkData[$this->tokenElementName])
        ) {
            throw new InvalidCSRFTokenException();
        }
    }

    /**
     * Check whether the form's CSRF token-field has a valid value
     *
     * @param   string $elementValue Value from the form element
     *
     * @return  bool
     */
    private function hasValidCsrfToken($elementValue)
    {
        if ($this->getElement($this->tokenElementName) === null) {
            return false;
        }
        if (strpos($elementValue, '|') === false) {
            return false;
        }

        list($seed, $token) = explode('|', $elementValue);

        if (!is_numeric($seed)) {
            return false;
        }

        return $token === hash('sha256', $this->getSessionId() . $seed);
    }

    /**
     * Add element decorators which apply to all elements
     *
     * Adds `HelpText` decorator
     *
     * @see HelpText
     */
    private function addElementDecorators()
    {
        foreach ($this->getElements() as $element) {
            $element->addDecorator(new HelpText());
        }
    }

    /**
     * Generate a new (seed, token) pair
     *
     * @return array
     */
    final public function generateCsrfToken()
    {
        $seed = mt_rand();
        $hash = hash('sha256', $this->getSessionId() . $seed);

        return array($seed, $hash);
    }

    /**
     * Return the string representation of the CSRF seed/token pair
     *
     * @return string
     */
    final public function generateCsrfTokenAsString()
    {
        list ($seed, $token) = $this->generateCsrfToken($this->getSessionId());
        return sprintf('%s|%s', $seed, $token);
    }
}
