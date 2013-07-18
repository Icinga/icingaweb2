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
     * @see Zend_Form::init
     */
    public function init()
    {
        if (!$this->tokenDisabled) {
            $this->initCsrfToken();
        }
    }

    /**
     * Render the form to html
     * @param  Zend_View_Interface $view
     * @return string
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
    abstract protected function create();

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
     *
     * @param null|\Zend_Controller_Request_Abstract|array $data
     * @return bool
     */
    public function isValid($data)
    {
        $check = null;

        // Elements must be there to validate
        $this->buildForm();

        if ($data === null) {
            $check = $this->getRequest()->getParams();
        } elseif ($data instanceof \Zend_Controller_Request_Abstract) {
            $check = $data->getParams();
        } else {
            $check = $data;
        }

        return parent::isValid($check);
    }

    /**
     * Enable CSRF counter measure
     */
    final public function enableCsrfToken()
    {
        $this->tokenDisabled = false;
    }

    /**
     * Disable CSRF counter measure and remove its field if already added
     */
    final public function disableCsrfToken()
    {
        $this->tokenDisabled = true;
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
        list($seed, $token) = $this->generateCsrfToken($this->tokenTimeout);

        $this->addElement(
            'hidden',
            $this->tokenElementName,
            array(
                'value'      => sprintf('%s\|/%s', $seed, $token),
                'decorators' => array('ViewHelper')
            )
        );
    }

    /**
     * Check whether the form's CSRF token-field has a valid value
     *
     * @param int    $maxAge    Max allowed token age
     * @param string $sessionId A specific session id
     *
     * @return bool
     */
    final private function hasValidCsrfToken($maxAge, $sessionId = null)
    {
        if ($this->tokenDisabled) {
            return true;
        }

        if ($this->getElement($this->tokenElementName) === null) {
            return false;
        }

        $elementValue = $this->getElement($this->tokenElementName)->getValue();
        list($seed, $token) = explode($elementValue, '\|/');

        if (!is_numeric($seed)) {
            return false;
        }

        $seed -= intval(time() / $maxAge) * $maxAge;
        $sessionId = $sessionId ? $sessionId : session_id();
        return $token === hash('sha256', $sessionId . $seed);
    }

    /**
     * Generate a new (seed, token) pair
     *
     * @param int    $maxAge    Max allowed token age
     * @param string $sessionId A specific session id
     *
     * @return array
     */
    final private function generateCsrfToken($maxAge, $sessionId = null)
    {
        $sessionId = $sessionId ? $sessionId : session_id();
        $seed = mt_rand();
        $hash = hash('sha256', $sessionId . $seed);
        $seed += intval(time() / $maxAge) * $maxAge;
        return array($seed, $hash);
    }
}
