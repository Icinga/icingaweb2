<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Form\Element;

use Zend_Form_Element_Xhtml;
use Icinga\Web\Session;
use Icinga\Web\Form\InvalidCSRFTokenException;

/**
 * CSRF counter measure element
 *
 * You must not set a value to successfully use this element, just give it a name and you're good to go.
 */
class CsrfCounterMeasure extends Zend_Form_Element_Xhtml
{
    /**
     * Default form view helper to use for rendering
     *
     * @var string
     */
    public $helper = 'formHidden';

    /**
     * Initialize this form element
     */
    public function init()
    {
        $this->setRequired(true); // Not requiring this element would not make any sense
        $this->setIgnore(true); // We do not want this element's value being retrieved by Form::getValues()
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
