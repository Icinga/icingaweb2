<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Element;

use Icinga\Web\Session;
use Icinga\Web\Form\FormElement;
use Icinga\Web\Form\InvalidCSRFTokenException;

/**
 * CSRF counter measure element
 *
 * You must not set a value to successfully use this element, just give it a name and you're good to go.
 */
class CsrfCounterMeasure extends FormElement
{
    /**
     * Default form view helper to use for rendering
     *
     * @var string
     */
    public $helper = 'formHidden';

    /**
     * Counter measure element is required
     *
     * @var bool
     */
    protected $_ignore = true;

    /**
     * Ignore element when retrieving values at form level
     *
     * @var bool
     */
    protected $_required = true;

    /**
     * Initialize this form element
     */
    public function init()
    {
        $this->addDecorator('ViewHelper');
        $this->setValue($this->generateCsrfToken());
    }

    /**
     * Check whether $value is a valid CSRF token
     *
     * @param   string      $value          The value to check
     * @param   mixed       $context        Context to use
     *
     * @return  bool                        True, in case the CSRF token is valid
     *
     * @throws  InvalidCSRFTokenException   In case the CSRF token is not valid
     */
    public function isValid($value, $context = null)
    {
        if (parent::isValid($value, $context) && $this->isValidCsrfToken($value)) {
            return true;
        }

        throw new InvalidCSRFTokenException();
    }

    /**
     * Check whether the given value is a valid CSRF token for the current session
     *
     * @param   string  $token  The CSRF token
     *
     * @return  bool
     */
    protected function isValidCsrfToken($token)
    {
        if (strpos($token, '|') === false) {
            return false;
        }

        list($seed, $hash) = explode('|', $token);

        if (false === is_numeric($seed)) {
            return false;
        }

        return $hash === hash('sha256', Session::getSession()->getId() . $seed);
    }

    /**
     * Generate a new (seed, token) pair
     *
     * @return  string
     */
    protected function generateCsrfToken()
    {
        $seed = mt_rand();
        $hash = hash('sha256', Session::getSession()->getId() . $seed);
        return sprintf('%s|%s', $seed, $hash);
    }
}
