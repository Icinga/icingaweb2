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
     * @see Zend_Form::init
     */
    public function init()
    {
        if (!$this->tokenDisabled) {
            $this->initCsrfToken();
        }
        $this->create();
    }

    /**
     * Add elements to this form (used by extending classes)
     */
    abstract public function create();

    /**
     * Apply a request object wherewith the form can work
     *
     * @param $request The request object of a session
     */
    public function setRequest($request)
    {
        $this->request = $request;
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

        $this->addElement('hidden', $this->tokenElementName, array(
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
