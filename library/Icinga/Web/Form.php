<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web;

use Zend_Form;
use Icinga\Web\Session;
use Icinga\Web\Form\Decorator\HelpText;
use Icinga\Web\Form\Decorator\ElementWrapper;
use Icinga\Web\Form\InvalidCSRFTokenException;

/**
 * Base class for forms providing CSRF protection, confirmation logic and auto submission
 */
class Form extends Zend_Form
{
    /**
     * The view script to use when rendering this form
     *
     * @var string
     */
    protected $viewScript;

    /**
     * Whether this form should NOT add random generated "challenge" tokens that are associated with the user's current
     * session in order to prevent Cross-Site Request Forgery (CSRF). It is the form's responsibility to verify the
     * existence and correctness of this token
     *
     * @var bool
     */
    protected $tokenDisabled = false;

    /**
     * Name of the CSRF token element
     *
     * @var string
     */
    protected $tokenElementName = 'CSRFToken';

    /**
     * Set the view script to use when rendering this form
     *
     * @param   string  $viewScript     The view script to use
     *
     * @return  self
     */
    public function setViewScript($viewScript)
    {
        $this->viewScript = $viewScript;
        return $this;
    }

    /**
     * Return the view script being used when rendering this form
     *
     * @return  string
     */
    public function getViewScript()
    {
        return $this->viewScript;
    }

    /**
     * Disable CSRF counter measure and remove its field if already added
     *
     * @param   bool    $disabled   Set true in order to disable CSRF protection for this form, otherwise false
     *
     * @return  self
     */
    public function setTokenDisabled($disabled = true)
    {
        $this->tokenDisabled = (bool) $disabled;

        if ($disabled && $this->getElement($this->tokenElementName) !== null) {
            $this->removeElement($this->tokenElementName);
        }

        return $this;
    }

    /**
     * Return whether CSRF counter measures are disabled for this form
     *
     * @return  bool
     */
    public function getTokenDisabled()
    {
        return $this->tokenDisabled;
    }

    /**
     * Set the name to use for the CSRF element
     *
     * @param   string  $name   The name to set
     *
     * @return  self
     */
    public function setTokenElementName($name)
    {
        $this->tokenElementName = $name;
        return $this;
    }

    /**
     * Return the name of the CSRF element
     *
     * @return  string
     */
    public function getTokenElementName()
    {
        return $this->tokenElementName;
    }

    /**
     * Create and return the elements to add to this form
     *
     * Intended to be implemented by concrete form classes.
     *
     * @return  array
     */
    public function createElements()
    {
        return array();
    }

    /**
     * Add a new element
     *
     * Additionally, all structural form element decorators by Zend are replaced with our own ones.
     *
     * @param   string|Zend_Form_Element    $element    String element type, or an object of type Zend_Form_Element
     * @param   string                      $name       The name of the element to add if $element is a string
     * @param   array                       $options    The options for the element if $element is a string
     *
     * @return  self
     *
     * @see     Zend_Form::addElement()
     */
    public function addElement($element, $name = null, $options = null)
    {
        parent::addElement($element, $name, $options);
        $el = $name !== null ? $this->getElement($name) : $element;

        if ($el) {
            if (strpos(strtolower(get_class($el)), 'hidden') !== false) {
                $el->setDecorators(array('ViewHelper'));
            } else {
                $el->removeDecorator('HtmlTag');
                $el->removeDecorator('Label');
                $el->removeDecorator('DtDdWrapper');
                $el->addDecorator(new ElementWrapper());
                $el->addDecorator(new HelpText());
            }
        }

        return $this;
    }

    /**
     * Load the default decorators
     *
     * Overwrites Zend_Form::loadDefaultDecorators to avoid having the HtmlTag-Decorator added
     *
     * @return  self
     */
    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return $this;
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            if ($this->viewScript) {
                $this->addDecorator('ViewScript', array('viewScript' => $this->viewScript));
            } else {
                $this->addDecorator('FormElements')
                    //->addDecorator('HtmlTag', array('tag' => 'dl', 'class' => 'zend_form'))
                    ->addDecorator('Form');
            }
        }

        return $this;
    }

    /**
     * Add CSRF counter measure field to this form
     */
    protected function initCsrfToken()
    {
        if (false === $this->tokenDisabled && $this->getElement($this->tokenElementName) === null) {
            $this->addElement(
                'hidden',
                $this->tokenElementName,
                array(
                    'value' => $this->generateCsrfToken()
                )
            );
        }
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

    /**
     * Test the submitted data for a correct CSRF token
     *
     * @param   array   $requestData        The POST data sent by the user
     *
     * @throws  InvalidCSRFTokenException   When CSRF Validation fails
     */
    protected function assertValidCsrfToken(array $requestData)
    {
        if (false === $this->tokenDisabled) {
            if (false === isset($requestData[$this->tokenElementName])
                || false === $this->isValidCsrfToken($requestData[$this->tokenElementName])
            ) {
                throw new InvalidCSRFTokenException();
            }
        }
    }

    /**
     * Check whether the given value is a valid CSRF token for the current session
     *
     * @param   string  $token  Value from the CSRF form element
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
}
