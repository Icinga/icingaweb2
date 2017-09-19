<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Zend_Config;
use Zend_Form;
use Zend_Form_Element;
use Zend_View_Interface;
use Icinga\Application\Icinga;
use Icinga\Authentication\Auth;
use Icinga\Exception\ProgrammingError;
use Icinga\Security\SecurityException;
use Icinga\Util\Translator;
use Icinga\Web\Form\ErrorLabeller;
use Icinga\Web\Form\Decorator\Autosubmit;
use Icinga\Web\Form\Element\CsrfCounterMeasure;

/**
 * Base class for forms providing CSRF protection, confirmation logic and auto submission
 *
 * @method \Zend_Form_Element[] getElements() {
 *     {@inheritdoc}
 *     @return \Zend_Form_Element[]
 * }
 */
class Form extends Zend_Form
{
    /**
     * The suffix to append to a field's hidden default field name
     */
    const DEFAULT_SUFFIX = '_default';

    /**
     * Identifier for notifications of type error
     */
    const NOTIFICATION_ERROR = 0;

    /**
     * Identifier for notifications of type warning
     */
    const NOTIFICATION_WARNING = 1;

    /**
     * Identifier for notifications of type info
     */
    const NOTIFICATION_INFO = 2;

    /**
     * Whether this form has been created
     *
     * @var bool
     */
    protected $created = false;

    /**
     * This form's parent
     *
     * Gets automatically set upon calling addSubForm().
     *
     * @var Form
     */
    protected $_parent;

    /**
     * Whether the form is an API target
     *
     * When the form is an API target, the form evaluates as submitted if the request method equals the form method.
     * That means, that the submit button and form identification are not taken into account. In addition, the CSRF
     * counter measure will not be added to the form's elements.
     *
     * @var bool
     */
    protected $isApiTarget = false;

    /**
     * The request associated with this form
     *
     * @var Request
     */
    protected $request;

    /**
     * The callback to call instead of Form::onSuccess()
     *
     * @var callable
     */
    protected $onSuccess;

    /**
     * Label to use for the standard submit button
     *
     * @var string
     */
    protected $submitLabel;

    /**
     * Label to use for showing the user an activity indicator when submitting the form
     *
     * @var string
     */
    protected $progressLabel;

    /**
     * The url to redirect to upon success
     *
     * @var Url
     */
    protected $redirectUrl;

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
     * Whether this form should add a UID element being used to distinct different forms posting to the same action
     *
     * @var bool
     */
    protected $uidDisabled = false;

    /**
     * Name of the form identification element
     *
     * @var string
     */
    protected $uidElementName = 'formUID';

    /**
     * Whether the form should validate the sent data when being automatically submitted
     *
     * @var bool
     */
    protected $validatePartial = false;

    /**
     * Whether element ids will be protected against collisions by appending a request-specific unique identifier
     *
     * @var bool
     */
    protected $protectIds = true;

    /**
     * The cue that is appended to each element's label if it's required
     *
     * @var string
     */
    protected $requiredCue = '*';

    /**
     * The descriptions of this form
     *
     * @var array
     */
    protected $descriptions;

    /**
     * The notifications of this form
     *
     * @var array
     */
    protected $notifications;

    /**
     * The hints of this form
     *
     * @var array
     */
    protected $hints;

    /**
     * Whether the Autosubmit decorator should be applied to this form
     *
     * If this is true, the Autosubmit decorator is being applied to this form instead of to each of its elements.
     *
     * @var bool
     */
    protected $useFormAutosubmit = false;

    /**
     * Authentication manager
     *
     * @var Auth|null
     */
    private $auth;

    /**
     * Default element decorators
     *
     * @var array
     */
    public static $defaultElementDecorators = array(
        array('Label', array('tag'=>'span', 'separator' => '', 'class' => 'control-label')),
        array('Help', array('placement' => 'APPEND')),
        array(array('labelWrap' => 'HtmlTag'), array('tag' => 'div', 'class' => 'control-label-group')),
        array('ViewHelper', array('separator' => '')),
        array('Errors', array('separator' => '')),
        array('HtmlTag', array('tag' => 'div', 'class' => 'control-group'))
    );

    /**
     * (non-PHPDoc)
     * @see \Zend_Form::construct() For the method documentation.
     */
    public function __construct($options = null)
    {
        // Zend's plugin loader reverses the order of added prefix paths thus trying our paths first before trying
        // Zend paths
        $this->addPrefixPaths(array(
            array(
                'prefix'    => 'Icinga\\Web\\Form\\Element\\',
                'path'      => Icinga::app()->getLibraryDir('Icinga/Web/Form/Element'),
                'type'      => static::ELEMENT
            ),
            array(
                'prefix'    => 'Icinga\\Web\\Form\\Decorator\\',
                'path'      => Icinga::app()->getLibraryDir('Icinga/Web/Form/Decorator'),
                'type'      => static::DECORATOR
            )
        ));

        parent::__construct($options);
    }

    /**
     * Set this form's parent
     *
     * @param   Form    $form
     *
     * @return  $this
     */
    public function setParent(Form $form)
    {
        $this->_parent = $form;
        return $this;
    }

    /**
     * Return this form's parent
     *
     * @return  Form
     */
    public function getParent()
    {
        return $this->_parent;
    }

    /**
     * Set a callback that is called instead of this form's onSuccess method
     *
     * It is called using the following signature: (Form $this).
     *
     * @param   callable    $onSuccess  Callback
     *
     * @return  $this
     *
     * @throws  ProgrammingError        If the callback is not callable
     */
    public function setOnSuccess($onSuccess)
    {
        if (! is_callable($onSuccess)) {
            throw new ProgrammingError('The option `onSuccess\' is not callable');
        }
        $this->onSuccess = $onSuccess;
        return $this;
    }

    /**
     * Set the label to use for the standard submit button
     *
     * @param   string  $label  The label to use for the submit button
     *
     * @return  $this
     */
    public function setSubmitLabel($label)
    {
        $this->submitLabel = $label;
        return $this;
    }

    /**
     * Return the label being used for the standard submit button
     *
     * @return  string
     */
    public function getSubmitLabel()
    {
        return $this->submitLabel;
    }

    /**
     * Set the label to use for showing the user an activity indicator when submitting the form
     *
     * @param   string  $label
     *
     * @return  $this
     */
    public function setProgressLabel($label)
    {
        $this->progressLabel = $label;
        return $this;
    }

    /**
     * Return the label to use for showing the user an activity indicator when submitting the form
     *
     * @return  string
     */
    public function getProgressLabel()
    {
        return $this->progressLabel;
    }

    /**
     * Set the url to redirect to upon success
     *
     * @param   string|Url  $url    The url to redirect to
     *
     * @return  $this
     *
     * @throws  ProgrammingError    In case $url is neither a string nor a instance of Icinga\Web\Url
     */
    public function setRedirectUrl($url)
    {
        if (is_string($url)) {
            $url = Url::fromPath($url, array(), $this->getRequest());
        } elseif (! $url instanceof Url) {
            throw new ProgrammingError('$url must be a string or instance of Icinga\Web\Url');
        }

        $this->redirectUrl = $url;
        return $this;
    }

    /**
     * Return the url to redirect to upon success
     *
     * @return  Url
     */
    public function getRedirectUrl()
    {
        if ($this->redirectUrl === null) {
            $this->redirectUrl = $this->getRequest()->getUrl();
            if ($this->getMethod() === 'get') {
                // Be sure to remove all form dependent params because we do not want to submit it again
                $this->redirectUrl = $this->redirectUrl->without(array_keys($this->getElements()));
            }
        }

        return $this->redirectUrl;
    }

    /**
     * Set the view script to use when rendering this form
     *
     * @param   string  $viewScript     The view script to use
     *
     * @return  $this
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
     * @return  $this
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
     * @return  $this
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
     * Disable form identification and remove its field if already added
     *
     * @param   bool    $disabled   Set true in order to disable identification for this form, otherwise false
     *
     * @return  $this
     */
    public function setUidDisabled($disabled = true)
    {
        $this->uidDisabled = (bool) $disabled;

        if ($disabled && $this->getElement($this->uidElementName) !== null) {
            $this->removeElement($this->uidElementName);
        }

        return $this;
    }

    /**
     * Return whether identification is disabled for this form
     *
     * @return  bool
     */
    public function getUidDisabled()
    {
        return $this->uidDisabled;
    }

    /**
     * Set the name to use for the form identification element
     *
     * @param   string  $name   The name to set
     *
     * @return  $this
     */
    public function setUidElementName($name)
    {
        $this->uidElementName = $name;
        return $this;
    }

    /**
     * Return the name of the form identification element
     *
     * @return  string
     */
    public function getUidElementName()
    {
        return $this->uidElementName;
    }

    /**
     * Set whether this form should validate the sent data when being automatically submitted
     *
     * @param   bool    $state
     *
     * @return  $this
     */
    public function setValidatePartial($state)
    {
        $this->validatePartial = $state;
        return $this;
    }

    /**
     * Return whether this form should validate the sent data when being automatically submitted
     *
     * @return  bool
     */
    public function getValidatePartial()
    {
        return $this->validatePartial;
    }

    /**
     * Set whether each element's id should be altered to avoid duplicates
     *
     * @param   bool    $value
     *
     * @return  Form
     */
    public function setProtectIds($value = true)
    {
        $this->protectIds = (bool) $value;
        return $this;
    }

    /**
     * Return whether each element's id is being altered to avoid duplicates
     *
     * @return  bool
     */
    public function getProtectIds()
    {
        return $this->protectIds;
    }

    /**
     * Set the cue to append to each element's label if it's required
     *
     * @param   string  $cue
     *
     * @return  Form
     */
    public function setRequiredCue($cue)
    {
        $this->requiredCue = $cue;
        return $this;
    }

    /**
     * Return the cue being appended to each element's label if it's required
     *
     * @return  string
     */
    public function getRequiredCue()
    {
        return $this->requiredCue;
    }

    /**
     * Set the descriptions for this form
     *
     * @param   array   $descriptions
     *
     * @return  Form
     */
    public function setDescriptions(array $descriptions)
    {
        $this->descriptions = $descriptions;
        return $this;
    }

    /**
     * Add a description for this form
     *
     * If $description is an array the second value should be
     * an array as well containing additional HTML properties.
     *
     * @param   string|array    $description
     *
     * @return  Form
     */
    public function addDescription($description)
    {
        $this->descriptions[] = $description;
        return $this;
    }

    /**
     * Return the descriptions of this form
     *
     * @return  array
     */
    public function getDescriptions()
    {
        if ($this->descriptions === null) {
            return array();
        }

        return $this->descriptions;
    }

    /**
     * Set the notifications for this form
     *
     * @param   array   $notifications
     *
     * @return  $this
     */
    public function setNotifications(array $notifications)
    {
        $this->notifications = $notifications;
        return $this;
    }

    /**
     * Add a notification for this form
     *
     * If $notification is an array the second value should be
     * an array as well containing additional HTML properties.
     *
     * @param   string|array    $notification
     * @param   int             $type
     *
     * @return  $this
     */
    public function addNotification($notification, $type)
    {
        $this->notifications[$type][] = $notification;
        return $this;
    }

    /**
     * Return the notifications of this form
     *
     * @return  array
     */
    public function getNotifications()
    {
        if ($this->notifications === null) {
            return array();
        }

        return $this->notifications;
    }

    /**
     * Set the hints for this form
     *
     * @param   array   $hints
     *
     * @return  $this
     */
    public function setHints(array $hints)
    {
        $this->hints = $hints;
        return $this;
    }

    /**
     * Add a hint for this form
     *
     * If $hint is an array the second value should be an
     * array as well containing additional HTML properties.
     *
     * @param   string|array    $hint
     *
     * @return  $this
     */
    public function addHint($hint)
    {
        $this->hints[] = $hint;
        return $this;
    }

    /**
     * Return the hints of this form
     *
     * @return  array
     */
    public function getHints()
    {
        if ($this->hints === null) {
            return array();
        }

        return $this->hints;
    }

    /**
     * Set whether the Autosubmit decorator should be applied to this form
     *
     * If true, the Autosubmit decorator is being applied to this form instead of to each of its elements.
     *
     * @param   bool    $state
     *
     * @return  Form
     */
    public function setUseFormAutosubmit($state = true)
    {
        $this->useFormAutosubmit = (bool) $state;
        if ($this->useFormAutosubmit) {
            $this->setAttrib('data-progress-element', 'header-' . $this->getId());
        } else {
            $this->removeAttrib('data-progress-element');
        }

        return $this;
    }

    /**
     * Return whether the Autosubmit decorator is being applied to this form
     *
     * @return  bool
     */
    public function getUseFormAutosubmit()
    {
        return $this->useFormAutosubmit;
    }

    /**
     * Get whether the form is an API target
     *
     * @return bool
     */
    public function getIsApiTarget()
    {
        return $this->isApiTarget;
    }

    /**
     * Set whether the form is an API target
     *
     * @param   bool $isApiTarget
     *
     * @return  $this
     */
    public function setIsApiTarget($isApiTarget = true)
    {
        $this->isApiTarget = (bool) $isApiTarget;
        return $this;
    }

    /**
     * Create this form
     *
     * @param   array   $formData   The data sent by the user
     *
     * @return  $this
     */
    public function create(array $formData = array())
    {
        if (! $this->created) {
            $this->createElements($formData);
            $this->addFormIdentification()
                ->addCsrfCounterMeasure()
                ->addSubmitButton();

            // Use Form::getAttrib() instead of Form::getAction() here because we want to explicitly check against
            // null. Form::getAction() would return the empty string '' if the action is not set.
            // For not setting the action attribute use Form::setAction(''). This is required for for the
            // accessibility's enable/disable auto-refresh mechanic
            if ($this->getAttrib('action') === null) {
                $action = $this->getRequest()->getUrl();
                if ($this->getMethod() === 'get') {
                    $action = $action->without(array_keys($this->getElements()));
                }

                // TODO(el): Re-evalute this necessity.
                // JavaScript could use the container'sURL if there's no action set.
                // We MUST set an action as JS gets confused otherwise, if
                // this form is being displayed in an additional column
                $this->setAction($action);
            }

            $this->created = true;
        }

        return $this;
    }

    /**
     * Create and add elements to this form
     *
     * Intended to be implemented by concrete form classes.
     *
     * @param   array   $formData   The data sent by the user
     */
    public function createElements(array $formData)
    {
    }

    /**
     * Perform actions after this form was submitted using a valid request
     *
     * Intended to be implemented by concrete form classes. The base implementation returns always FALSE.
     *
     * @return  null|bool               Return FALSE in case no redirect should take place
     */
    public function onSuccess()
    {
        return false;
    }

    /**
     * Perform actions when no form dependent data was sent
     *
     * Intended to be implemented by concrete form classes.
     */
    public function onRequest()
    {
    }

    /**
     * Add a submit button to this form
     *
     * Uses the label previously set with Form::setSubmitLabel(). Overwrite this
     * method in order to add multiple submit buttons or one with a custom name.
     *
     * @return  $this
     */
    public function addSubmitButton()
    {
        $submitLabel = $this->getSubmitLabel();
        if ($submitLabel) {
            $this->addElement(
                'submit',
                'btn_submit',
                array(
                    'ignore'                => true,
                    'label'                 => $submitLabel,
                    'data-progress-label'   => $this->getProgressLabel(),
                    'decorators'            => array(
                        'ViewHelper',
                        array('Spinner', array('separator' => '')),
                        array('HtmlTag', array('tag' => 'div', 'class' => 'control-group form-controls'))
                    )
                )
            );
        }

        return $this;
    }

    /**
     * Add a subform
     *
     * @param   Zend_Form   $form   The subform to add
     * @param   string      $name   The name of the subform or null to use the name of $form
     * @param   int         $order  The location where to insert the form
     *
     * @return  Zend_Form
     */
    public function addSubForm(Zend_Form $form, $name = null, $order = null)
    {
        if ($form instanceof self) {
            $form->setDecorators(array('FormElements')); // TODO: Makes it difficult to customise subform decorators..
            $form->setSubmitLabel('');
            $form->setTokenDisabled();
            $form->setUidDisabled();
            $form->setParent($this);
        }

        if ($name === null) {
            $name = $form->getName();
        }

        return parent::addSubForm($form, $name, $order);
    }

    /**
     * Create a new element
     *
     * Icinga Web 2 loads its own default element decorators. For loading Zend's default element decorators set the
     * `disableLoadDefaultDecorators' option to any other value than `true'. For loading custom element decorators use
     * the 'decorators' option.
     *
     * @param   string  $type       The type of the element
     * @param   string  $name       The name of the element
     * @param   mixed   $options    The options for the element
     *
     * @return  Zend_Form_Element
     *
     * @see     Form::$defaultElementDecorators For Icinga Web 2's default element decorators.
     */
    public function createElement($type, $name, $options = null)
    {
        if ($options !== null) {
            if ($options instanceof Zend_Config) {
                $options = $options->toArray();
            }
            if (! isset($options['decorators'])
                && ! array_key_exists('disabledLoadDefaultDecorators', $options)
            ) {
                $options['decorators'] = static::$defaultElementDecorators;
                if (! isset($options['data-progress-label']) && ($type === 'submit'
                    || ($type === 'button' && isset($options['type']) && $options['type'] === 'submit'))
                ) {
                    array_splice($options['decorators'], 1, 0, array(array('Spinner', array('separator' => ''))));
                } elseif ($type === 'hidden') {
                    $options['decorators'] = array('ViewHelper');
                }
            }
        } else {
            $options = array('decorators' => static::$defaultElementDecorators);
            if ($type === 'submit') {
                array_splice($options['decorators'], 1, 0, array(array('Spinner', array('separator' => ''))));
            } elseif ($type === 'hidden') {
                $options['decorators'] = array('ViewHelper');
            }
        }

        $el = parent::createElement($type, $name, $options);
        $el->setTranslator(new ErrorLabeller(array('element' => $el)));

        $el->addPrefixPaths(array(
            array(
                'prefix'    => 'Icinga\\Web\\Form\\Validator\\',
                'path'      => Icinga::app()->getLibraryDir('Icinga/Web/Form/Validator'),
                'type'      => $el::VALIDATE
            )
        ));

        if ($this->protectIds) {
            $el->setAttrib('id', $this->getRequest()->protectId($this->getId(false) . '_' . $el->getId()));
        }

        if ($el->getAttrib('autosubmit')) {
            if ($this->getUseFormAutosubmit()) {
                $warningId = 'autosubmit_warning_' . $el->getId();
                $warningText = $this->getView()->escape($this->translate(
                    'This page will be automatically updated upon change of the value'
                ));
                $autosubmitDecorator = $this->_getDecorator('Callback', array(
                    'placement' => 'PREPEND',
                    'callback'  => function ($content) use ($warningId, $warningText) {
                        return '<span class="sr-only" id="' . $warningId . '">' . $warningText . '</span>';
                    }
                ));
            } else {
                $autosubmitDecorator = new Autosubmit();
                $autosubmitDecorator->setAccessible();
                $warningId = $autosubmitDecorator->getWarningId($el);
            }

            $decorators = $el->getDecorators();
            $pos = array_search('Zend_Form_Decorator_ViewHelper', array_keys($decorators), true) + 1;
            $el->setDecorators(
                array_slice($decorators, 0, $pos, true)
                + array('autosubmit' => $autosubmitDecorator)
                + array_slice($decorators, $pos, count($decorators) - $pos, true)
            );

            if (($describedBy = $el->getAttrib('aria-describedby')) !== null) {
                $el->setAttrib('aria-describedby', $describedBy . ' ' . $warningId);
            } else {
                $el->setAttrib('aria-describedby', $warningId);
            }

            $class = $el->getAttrib('class');
            if (is_array($class)) {
                $class[] = 'autosubmit';
            } elseif ($class === null) {
                $class = 'autosubmit';
            } else {
                $class .= ' autosubmit';
            }
            $el->setAttrib('class', $class);

            unset($el->autosubmit);
        }

        if ($el->getAttrib('preserveDefault')) {
            $el->addDecorator(
                array('preserveDefault' => 'HtmlTag'),
                array(
                    'tag'   => 'input',
                    'type'  => 'hidden',
                    'name'  => $name . static::DEFAULT_SUFFIX,
                    'value' => $el->getValue()
                )
            );

            unset($el->preserveDefault);
        }

        return $this->ensureElementAccessibility($el);
    }

    /**
     * Add accessibility related attributes
     *
     * @param   Zend_Form_Element   $element
     *
     * @return  Zend_Form_Element
     */
    public function ensureElementAccessibility(Zend_Form_Element $element)
    {
        if ($element->isRequired() && strpos(strtolower($element->getType()), 'checkbox') === false) {
            $element->setAttrib('aria-required', 'true'); // ARIA
            $element->setAttrib('required', ''); // HTML5
            if (($cue = $this->getRequiredCue()) !== null && ($label = $element->getDecorator('label')) !== false) {
                $element->setLabel($this->getView()->escape($element->getLabel()));
                $label->setOption('escape', false);
                $label->setRequiredSuffix(sprintf(' <span aria-hidden="true">%s</span>', $cue));
            }
        }

        if ($element->getDescription() !== null && ($help = $element->getDecorator('help')) !== false) {
            if (($describedBy = $element->getAttrib('aria-describedby')) !== null) {
                // Assume that it's because of the element being of type autosubmit or
                // that one who did set the property manually removes the help decorator
                // in case it has already an aria-describedby property set
                $element->setAttrib(
                    'aria-describedby',
                    $help->setAccessible()->getDescriptionId($element) . ' ' . $describedBy
                );
            } else {
                $element->setAttrib('aria-describedby', $help->setAccessible()->getDescriptionId($element));
            }
        }

        return $element;
    }

    /**
     * Add a field with a unique and form specific ID
     *
     * @return  $this
     */
    public function addFormIdentification()
    {
        if (! $this->uidDisabled && $this->getElement($this->uidElementName) === null) {
            $this->addElement(
                'hidden',
                $this->uidElementName,
                array(
                    'ignore'        => true,
                    'value'         => $this->getName(),
                    'decorators'    => array('ViewHelper')
                )
            );
        }

        return $this;
    }

    /**
     * Add CSRF counter measure field to this form
     *
     * @return  $this
     */
    public function addCsrfCounterMeasure()
    {
        if (! $this->tokenDisabled) {
            $request = $this->getRequest();
            if (! $request->isXmlHttpRequest()
                && ($this->getIsApiTarget() || $request->isApiRequest())
            ) {
                return $this;
            }
            if ($this->getElement($this->tokenElementName) === null) {
                $this->addElement(new CsrfCounterMeasure($this->tokenElementName));
            }
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * Creates the form if not created yet.
     *
     * @param   array   $values
     *
     * @return  $this
     */
    public function setDefaults(array $values)
    {
        $this->create($values);
        return parent::setDefaults($values);
    }

    /**
     * Populate the elements with the given values
     *
     * @param   array   $defaults   The values to populate the elements with
     *
     * @return  $this
     */
    public function populate(array $defaults)
    {
        $this->create($defaults);
        $this->preserveDefaults($this, $defaults);
        return parent::populate($defaults);
    }

    /**
     * Recurse the given form and unset all unchanged default values
     *
     * @param   Zend_Form   $form
     * @param   array       $defaults
     */
    protected function preserveDefaults(Zend_Form $form, array & $defaults)
    {
        foreach ($form->getElements() as $name => $element) {
            if ((array_key_exists($name, $defaults)
                    && array_key_exists($name . static::DEFAULT_SUFFIX, $defaults)
                    && $defaults[$name] === $defaults[$name . static::DEFAULT_SUFFIX])
                || $element->getAttrib('disabled')
            ) {
                unset($defaults[$name]);
            }
        }

        foreach ($form->getSubForms() as $_ => $subForm) {
            $this->preserveDefaults($subForm, $defaults);
        }
    }

    /**
     * Process the given request using this form
     *
     * Redirects to the url set with setRedirectUrl() upon success. See onSuccess()
     * and onRequest() wherewith you can customize the processing logic.
     *
     * @param   Request     $request    The request to be processed
     *
     * @return  Request                 The request supposed to be processed
     */
    public function handleRequest(Request $request = null)
    {
        if ($request === null) {
            $request = $this->getRequest();
        } else {
            $this->request = $request;
        }

        $formData = $this->getRequestData();
        if ($this->getIsApiTarget() || $this->getUidDisabled() || $this->wasSent($formData)) {
            if (($frameUpload = (bool) $request->getUrl()->shift('_frameUpload', false))) {
                $this->getView()->layout()->setLayout('wrapped');
            }
            $this->populate($formData); // Necessary to get isSubmitted() to work
            if (! $this->getSubmitLabel() || $this->isSubmitted()) {
                if ($this->isValid($formData)
                    && (($this->onSuccess !== null && false !== call_user_func($this->onSuccess, $this))
                        || ($this->onSuccess === null && false !== $this->onSuccess()))
                ) {
                    if ($this->getIsApiTarget() || $this->getRequest()->isApiRequest()) {
                        // API targets and API requests will never redirect but immediately respond w/ JSON-encoded
                        // notifications
                        $notifications = Notification::getInstance()->popMessages();
                        $message = null;
                        foreach ($notifications as $notification) {
                            if ($notification->type === Notification::SUCCESS) {
                                $message = $notification->message;
                                break;
                            }
                        }
                        $this->getResponse()->json()
                            ->setSuccessData($message !== null ? array('message' => $message) : null)
                            ->sendResponse();
                    } elseif (! $frameUpload) {
                        $this->getResponse()->redirectAndExit($this->getRedirectUrl());
                    } else {
                        $this->getView()->layout()->redirectUrl = $this->getRedirectUrl()->getAbsoluteUrl();
                    }
                } elseif ($this->getIsApiTarget()) {
                    $this->getResponse()->json()->setFailData($this->getMessages())->sendResponse();
                }
            } elseif ($this->getValidatePartial()) {
                // The form can't be processed but we may want to show validation errors though
                $this->isValidPartial($formData);
            }
        } else {
            $this->onRequest();
        }

        return $request;
    }

    /**
     * Return whether the submit button of this form was pressed
     *
     * When overwriting Form::addSubmitButton() be sure to overwrite this method as well.
     *
     * @return  bool                True in case it was pressed, False otherwise or no submit label was set
     */
    public function isSubmitted()
    {
        if (strtolower($this->getRequest()->getMethod()) !== $this->getMethod()) {
            return false;
        }
        if ($this->getIsApiTarget()) {
            return true;
        }
        if ($this->getSubmitLabel()) {
            return $this->getElement('btn_submit')->isChecked();
        }

        return false;
    }

    /**
     * Return whether the data sent by the user refers to this form
     *
     * Ensures that the correct form gets processed in case there are multiple forms
     * with equal submit button names being posted against the same route.
     *
     * @param   array   $formData   The data sent by the user
     *
     * @return  bool                Whether the given data refers to this form
     */
    public function wasSent(array $formData)
    {
        return isset($formData[$this->uidElementName]) && $formData[$this->uidElementName] === $this->getName();
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

        foreach ($this->getElements() as $name => $element) {
            if (array_key_exists($name, $formData)) {
                if ($element->getAttrib('disabled')) {
                    // Ensure that disabled elements are not overwritten
                    // (http://www.zendframework.com/issues/browse/ZF-6909)
                    $formData[$name] = $element->getValue();
                } elseif (array_key_exists($name . static::DEFAULT_SUFFIX, $formData)
                    && $formData[$name] === $formData[$name . static::DEFAULT_SUFFIX]
                ) {
                    unset($formData[$name]);
                }
            }
        }

        return parent::isValidPartial($formData);
    }

    /**
     * Return whether the given values are valid
     *
     * @param   array   $formData   The data to validate
     *
     * @return  bool
     */
    public function isValid($formData)
    {
        $this->create($formData);

        // Ensure that disabled elements are not overwritten (http://www.zendframework.com/issues/browse/ZF-6909)
        foreach ($this->getElements() as $name => $element) {
            if ($element->getAttrib('disabled')) {
                $formData[$name] = $element->getValue();
            }
        }

        return parent::isValid($formData);
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
     * the HtmlTag-Decorator added and to provide view script usage
     *
     * @return  $this
     */
    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return $this;
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            if ($this->viewScript) {
                $this->addDecorator('ViewScript', array(
                    'viewScript'    => $this->viewScript,
                    'form'          => $this
                ));
            } else {
                $this->addDecorator('Description', array('tag' => 'h1'));
                if ($this->getUseFormAutosubmit()) {
                    $this->getDecorator('Description')->setEscape(false);
                    $this->addDecorator(
                        'HtmlTag',
                        array(
                            'tag'   => 'div',
                            'class' => 'header',
                            'id'    => 'header-' . $this->getId()
                        )
                    );
                }

                $this->addDecorator('FormDescriptions')
                    ->addDecorator('FormNotifications')
                    ->addDecorator('FormErrors', array('onlyCustomFormErrors' => true))
                    ->addDecorator('FormElements')
                    ->addDecorator('FormHints')
                    //->addDecorator('HtmlTag', array('tag' => 'dl', 'class' => 'zend_form'))
                    ->addDecorator('Form');
            }
        }

        return $this;
    }

    /**
     * Get element id
     *
     * Returns the protected id, in case id protection is enabled.
     *
     * @param   bool    $protect
     *
     * @return  string
     */
    public function getId($protect = true)
    {
        $id = parent::getId();
        return $protect && $this->protectIds ? $this->getRequest()->protectId($id) : $id;
    }

    /**
     * Return the name of this form
     *
     * @return  string
     */
    public function getName()
    {
        $name = parent::getName();
        if (! $name) {
            $name = get_class($this);
            $this->setName($name);
            $name = parent::getName();
        }
        return $name;
    }

    /**
     * Retrieve form description
     *
     * This will return the escaped description with the autosubmit warning icon if form autosubmit is enabled.
     *
     * @return  string
     */
    public function getDescription()
    {
        $description = parent::getDescription();
        if ($description && $this->getUseFormAutosubmit()) {
            $autosubmit = $this->_getDecorator('Autosubmit', array('accessible' => true));
            $autosubmit->setElement($this);
            $description = $autosubmit->render($this->getView()->escape($description));
        }

        return $description;
    }

    /**
     * Set the action to submit this form against
     *
     * Note that if you'll pass a instance of URL, Url::getAbsoluteUrl('&') is called to set the action.
     *
     * @param   Url|string  $action
     *
     * @return  $this
     */
    public function setAction($action)
    {
        if ($action instanceof Url) {
            $action = $action->getAbsoluteUrl('&');
        }

        return parent::setAction($action);
    }

    /**
     * Set form description
     *
     * Alias for Zend_Form::setDescription().
     *
     * @param   string  $value
     *
     * @return  Form
     */
    public function setTitle($value)
    {
        return $this->setDescription($value);
    }

    /**
     * Return the request associated with this form
     *
     * Returns the global request if none has been set for this form yet.
     *
     * @return  Request
     */
    public function getRequest()
    {
        if ($this->request === null) {
            $this->request = Icinga::app()->getRequest();
        }

        return $this->request;
    }

    /**
     * Set the request
     *
     * @param   Request $request
     *
     * @return  $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Return the current Response
     *
     * @return  Response
     */
    public function getResponse()
    {
        return Icinga::app()->getFrontController()->getResponse();
    }

    /**
     * Return the request data based on this form's request method
     *
     * @return  array
     */
    protected function getRequestData()
    {
        if (strtolower($this->request->getMethod()) === $this->getMethod()) {
            return $this->request->{'get' . ($this->request->isPost() ? 'Post' : 'Query')}();
        }

        return array();
    }

    /**
     * Get the translation domain for this form
     *
     * The returned translation domain is either determined based on this form's qualified name or it is the default
     * 'icinga' domain
     *
     * @return string
     */
    protected function getTranslationDomain()
    {
        $parts = explode('\\', get_called_class());
        if (count($parts) > 1 && $parts[1] === 'Module') {
            // Assume format Icinga\Module\ModuleName\Forms\...
            return strtolower($parts[2]);
        }

        return 'icinga';
    }

    /**
     * Translate a string
     *
     * @param   string      $text       The string to translate
     * @param   string|null $context    Optional parameter for context based translation
     *
     * @return  string                  The translated string
     */
    protected function translate($text, $context = null)
    {
        return Translator::translate($text, $this->getTranslationDomain(), $context);
    }

    /**
     * Translate a plural string
     *
     * @param   string      $textSingular   The string in singular form to translate
     * @param   string      $textPlural     The string in plural form to translate
     * @param   integer     $number         The amount to determine from whether to return singular or plural
     * @param   string|null $context        Optional parameter for context based translation
     *
     * @return  string                      The translated string
     */
    protected function translatePlural($textSingular, $textPlural, $number, $context = null)
    {
        return Translator::translatePlural(
            $textSingular,
            $textPlural,
            $number,
            $this->getTranslationDomain(),
            $context
        );
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

    /**
     * Get the authentication manager
     *
     * @return Auth
     */
    public function Auth()
    {
        if ($this->auth === null) {
            $this->auth = Auth::getInstance();
        }
        return $this->auth;
    }

    /**
     * Whether the current user has the given permission
     *
     * @param   string  $permission Name of the permission
     *
     * @return  bool
     */
    public function hasPermission($permission)
    {
        return $this->Auth()->hasPermission($permission);
    }

    /**
     * Assert that the current user has the given permission
     *
     * @param   string  $permission     Name of the permission
     *
     * @throws  SecurityException       If the current user lacks the given permission
     */
    public function assertPermission($permission)
    {
        if (! $this->Auth()->hasPermission($permission)) {
            throw new SecurityException('No permission for %s', $permission);
        }
    }

    /**
     * Add a error notification
     *
     * @param   string|array    $message        The notification message
     * @param   bool            $markAsError    Whether to prevent the form from being successfully validated or not
     *
     * @return  $this
     */
    public function error($message, $markAsError = true)
    {
        if ($this->getIsApiTarget()) {
            $this->addErrorMessage($message);
        } else {
            $this->addNotification($message, self::NOTIFICATION_ERROR);
        }

        if ($markAsError) {
            $this->markAsError();
        }

        return $this;
    }

    /**
     * Add a warning notification
     *
     * @param   string|array    $message        The notification message
     * @param   bool            $markAsError    Whether to prevent the form from being successfully validated or not
     *
     * @return  $this
     */
    public function warning($message, $markAsError = true)
    {
        if ($this->getIsApiTarget()) {
            $this->addErrorMessage($message);
        } else {
            $this->addNotification($message, self::NOTIFICATION_WARNING);
        }

        if ($markAsError) {
            $this->markAsError();
        }

        return $this;
    }

    /**
     * Add a info notification
     *
     * @param   string|array    $message        The notification message
     * @param   bool            $markAsError    Whether to prevent the form from being successfully validated or not
     *
     * @return  $this
     */
    public function info($message, $markAsError = true)
    {
        if ($this->getIsApiTarget()) {
            $this->addErrorMessage($message);
        } else {
            $this->addNotification($message, self::NOTIFICATION_INFO);
        }

        if ($markAsError) {
            $this->markAsError();
        }

        return $this;
    }
}
