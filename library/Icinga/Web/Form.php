<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web;

use LogicException;
use Zend_Form;
use Zend_View_Interface;
use Icinga\Web\Form\Decorator\HelpText;
use Icinga\Web\Form\Decorator\ElementWrapper;
use Icinga\Web\Form\Element\CsrfCounterMeasure;

/**
 * Base class for forms providing CSRF protection, confirmation logic and auto submission
 */
class Form extends Zend_Form
{
    /**
     * Whether this form has been created
     *
     * @var bool
     */
    protected $created = false;

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
     * Create this form
     *
     * @param   array   $formData   The data sent by the user
     *
     * @return  self
     */
    public function create(array $formData = array())
    {
        if (false === $this->created) {
            $this->addElements($this->createElements($formData));
            $this->addCsrfCounterMeasure()->addSubmitButton();

            if ($this->getAction() === '') {
                // We MUST set an action as JS gets confused otherwise, if
                // this form is being displayed in an additional column
                $this->setAction(Url::fromRequest()->getUrlWithout(array_keys($this->getElements())));
            }

            $this->created = true;
        }

        return $this;
    }

    /**
     * Create and return the elements to add to this form
     *
     * Intended to be implemented by concrete form classes.
     *
     * @param   array   $formData   The data sent by the user
     *
     * @return  array
     */
    public function createElements(array $formData)
    {
        return array();
    }

    /**
     * Add a submit button to this form
     *
     * Intended to be implemented by concrete form classes.
     *
     * @return  self
     */
    public function addSubmitButton()
    {
        return $this;
    }

    /**
     * Create a new element
     *
     * Additionally, all structural form element decorators by Zend are replaced with our own ones.
     *
     * @param   string  $type       String element type
     * @param   string  $name       The name of the element to add
     * @param   array   $options    The options for the element
     *
     * @return  Zend_Form_Element
     *
     * @see     Zend_Form::createElement()
     */
    public function createElement($type, $name, $options = null)
    {
        $el = parent::createElement($type, $name, $options);

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

        return $el;
    }

    /**
     * Add CSRF counter measure field to this form
     *
     * @return  self
     */
    public function addCsrfCounterMeasure()
    {
        if (false === $this->tokenDisabled && $this->getElement($this->tokenElementName) === null) {
            $element = new CsrfCounterMeasure($this->tokenElementName);
            $element->setDecorators(array('ViewHelper'));
            $this->addElement($element);
        }

        return $this;
    }

    /**
     * Populate the elements with the given values
     *
     * @param   array   $defaults   The values to populate the elements with
     */
    public function setDefaults(array $defaults)
    {
        $this->create($defaults);
        return parent::setDefaults($defaults);
    }

    /**
     * Return whether the given data provides a value for each element of this form
     *
     * Note that elements that are disabled or of type submit are not taken into consideration.
     *
     * @param   array   $formData   The data to check
     *
     * @return  bool
     *
     * @throws  LogicException      In case this form has no elements
     */
    public function isComplete(array $formData)
    {
        $elements = $this->create($formData)->getElements();
        if (empty($elements)) {
            throw new LogicException('Forms without elements cannot be complete');
        }

        $missingValues = array_diff_key(
            array_filter(
                $elements,
                function ($el) {
                    return $el->getAttrib('disabled') === null
                        && 0 === preg_match('@(submit|button)@', strtolower(get_class($el)));
                }
            ),
            $formData
        );
        return empty($missingValues);
    }

    /**
     * Return whether the given values (possibly incomplete) are valid
     *
     * Unlike Zend_Form::isValid() this will not set NULL as value for
     * an element that is not present in the given data.
     *
     * @param   array   $formData   The data to validate
     *
     * @return  bool
     */
    public function isValidPartial(array $formData)
    {
        $this->create($formData);
        return parent::isValidPartial($formData);
    }

    /**
     * Return whether the given values are complete and valid
     *
     * @param   array   $formData   The data to validate
     *
     * @return  bool
     */
    public function isValid($formData)
    {
        if ($this->isComplete($formData)) {
            return parent::isValid($formData);
        }

        $this->isValidPartial($formData); // The form can't be processed but we want to show validation errors though
        return false;
    }

    /**
     * Remove all elements of this form
     *
     * @return  self
     */
    public function clearElements()
    {
        $this->created = false;
        return parent::clearElements();
    }

    /**
     * Load the default decorators
     *
     * Overwrites Zend_Form::loadDefaultDecorators to avoid having
     * the HtmlTag-Decorator added and to provide viewscript usage
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
     * Render this form
     *
     * @param   Zend_View_Interface     $view   The view context to use
     *
     * @return  string
     */
    public function render(Zend_View_Interface $view = null)
    {
        $this->create();
        return parent::render($view);
    }
}
