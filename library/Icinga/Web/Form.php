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

use Icinga\Exception\ProgrammingError;
use Icinga\Web\Form\InvalidCSRFTokenException;
use Zend_Form_Exception;
use Zend_View_Interface;

/**
 * Class Form
 *
 * How forms are used in Icinga 2 Web
 */
abstract class Form extends \Zend_Form
{
    /**
     * The form's request object
     * @var null
     */
    private $request = null;

    /**
     * Whether this form should NOT add random generated "challenge" tokens that are associated
     * with the user's current session in order to prevent Cross-Site Request Forgery (CSRF).
     * It is the form's responsibility to verify the existence and correctness of this token
     * @var bool
     */
    private $tokenDisabled = false;

    /**
     * Name of the CSRF token element (used to create non-colliding hashes)
     * @var string
     */
    private $tokenElementName = 'CSRFToken';

    /**
     * Time to live for the CRSF token
     * @var int
     */
    private $tokenTimeout = 300;

    /**
     * Flag to indicate that form is already build
     * @var bool
     */
    private $created = false;

    /**
     * Session id required for CSRF token generation
     * @var numeric|bool
     */
    private $sessionId = false;

    /**
     * Returns the session ID stored in this form instance
     * @return mixed
     */
    public function getSessionId()
    {
        if (!$this->sessionId) {
            $this->sessionId = session_id();
        }
        return $this->sessionId;
    }

    /**
     * Overwrites the currently set session id to a user
     * provided one, helpful when testing
     *
     * @param $sessionId    The session id to use for CSRF generation
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * @see Zend_Form::init
     */
    public function init()
    {

    }

    /**
     * Returns the html-element name of the CSRF token
     * field
     *
     * @return string
     */
    public function getTokenElementName()
    {
        return $this->tokenElementName;
    }


    /**
     * Render the form to html
     * @param  Zend_View_Interface $view
     * @return string
     */
    public function render(Zend_View_Interface $view = null)
    {
        // Elements must be there to render the form
        return parent::render($view);
    }

    /**
     * Add elements to this form (used by extending classes)
     */
    abstract protected function create();

    /**
     * Method called before validation
     */
    protected function preValidation(array $data)
    {
    }

    /**
     * Setter for request
     * @param \Zend_Controller_Request_Abstract $request The request object of a session
     */
    public function setRequest(\Zend_Controller_Request_Abstract $request)
    {
        $this->request = $request;
    }

    /**
     * Getter for request
     * @return \Zend_Controller_Request_Abstract
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Triggers form creation
     */
    public function buildForm()
    {
        if ($this->created === false) {
            $this->initCsrfToken();
            $this->create();

            // Empty action if not safe
            if (!$this->getAction() && $this->getRequest()) {
                $this->setAction($this->getRequest()->getRequestUri());
            }

            $this->created = true;
        }
    }



    /**
     * Test if data from array or request is valid
     *
     * If $data is null, internal request is selected to test validity
     * @return bool
     */
    public function isPostAndValid()
    {
        if ($this->getRequest()->isPost() === false) {
            return false;
        }

        $checkData = $this->getRequest()->getParams();

        $this->buildForm();
        $this->assertValidCsrfToken($checkData);
        $this->preValidation($checkData);
        return parent::isValid($checkData);
    }



    /**
     * Disable CSRF counter measure and remove its field if already added
     */
    final public function setTokenDisabled($value)
    {
        $this->tokenDisabled = $value;
        if ($value == true)
            $this->removeElement($this->tokenElementName);
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
                'value'      => $this->generateCsrfTokenAsString(),
                'decorators' => array('ViewHelper')
            )
        );
    }

    /**
     * Tests the submitted data for a correct CSRF token, if needed
     *
     * @param Array $checkData                  The POST data send by the user
     * @throws Form\InvalidCSRFTokenException   When CSRF Validation fails
     */
    final public function assertValidCsrfToken(array $checkData)
    {
        if ($this->tokenDisabled) {
            return;
        }

        if (!isset($checkData[$this->tokenElementName]) || !$this->hasValidCsrfToken($checkData[$this->tokenElementName])) {
            throw new InvalidCSRFTokenException();
        }
    }

    /**
     * Check whether the form's CSRF token-field has a valid value
     *
     * @param int    $maxAge    Max allowed token age
     *
     * @return bool
     */
    final private function hasValidCsrfToken($checkData)
    {

        if ($this->getElement($this->tokenElementName) === null) {
            return false;
        }

        $elementValue = $checkData;
        if (strpos($elementValue, '|') === false) {
            return false;
        }


        list($seed, $token) = explode('|', $elementValue);

        if (!is_numeric($seed)) {
            return false;
        }

        $seed -= intval(time() / $this->tokenTimeout) * $this->tokenTimeout;

        return $token === hash('sha256', $this->getSessionId() . $seed);
    }

    /**
     * Generate a new (seed, token) pair
     *
     * @param int    $maxAge    Max allowed token age
     *
     * @return array
     */
    final public function generateCsrfToken()
    {
        $seed = mt_rand();
        $hash = hash('sha256', $this->getSessionId() . $seed);
        $seed += intval(time() / $this->tokenTimeout) * $this->tokenTimeout;

        return array($seed, $hash);
    }

    /**
     * Returns the string representation of the CSRF seed/token pair
     *
     * @return string
     */
    final public function generateCsrfTokenAsString()
    {
        list ($seed, $token) = $this->generateCsrfToken($this->getSessionId());
        return sprintf('%s|%s', $seed, $token);
    }
}
