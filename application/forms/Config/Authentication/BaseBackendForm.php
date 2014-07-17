<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config\Authentication;

use \Zend_Config;
use \Zend_Form_Element_Checkbox;
use Icinga\Web\Form;
use Icinga\Data\ResourceFactory;
use Icinga\Web\Form\Decorator\HelpText;

/**
 * Base form for authentication backend forms
 */
abstract class BaseBackendForm extends Form
{
    /**
     * The name of the backend currently displayed in this form
     *
     * Will be the section in the authentication.ini file
     *
     * @var string
     */
    protected $backendName = '';

    /**
     * The backend configuration as a Zend_Config object
     *
     * @var Zend_Config
     */
    protected $backend;

    /**
     * The resources to use instead of the factory provided ones (use for testing)
     *
     * @var Zend_Config
     */
    protected $resources;

    /**
     * Set the name of the currently displayed backend
     *
     * @param   string  $name   The name to be stored as the section when persisting
     */
    public function setBackendName($name)
    {
        $this->backendName = $name;
    }

    /**
     * Return the backend name of this form
     *
     * @return  string
     */
    public function getBackendName()
    {
        return $this->backendName;
    }

    /**
     * Return the backend configuration or a empty Zend_Config object if none is given
     *
     * @return  Zend_Config
     */
    public function getBackend()
    {
        return ($this->backend !== null) ? $this->backend : new Zend_Config(array());
    }

    /**
     * Set the backend configuration for initial population
     *
     * @param   Zend_Config     $backend    The backend to display in this form
     */
    public function setBackend(Zend_Config $backend)
    {
        $this->backend = $backend;
    }

    /**
     * Set an alternative array of resources that should be used instead of the DBFactory resource set
     *
     * @param   array   $resources      The resources to use for populating the db selection field
     */
    public function setResources(array $resources)
    {
        $this->resources = $resources;
    }

    /**
     * Return content of the resources.ini or previously set resources
     *
     * @return  array
     */
    public function getResources()
    {
        if ($this->resources === null) {
            return ResourceFactory::getResourceConfigs()->toArray();
        } else {
            return $this->resources;
        }
    }

    /**
     * Add checkbox at the beginning of the form which allows to skip logic connection validation
     */
    protected function addForceCreationCheckbox()
    {
        $checkbox = new Zend_Form_Element_Checkbox(
            array(
                'name'      =>  'backend_force_creation',
                'label'     =>  t('Force Changes'),
                'helptext'  =>  t('Check this box to enforce changes without connectivity validation'),
                'order'     =>  0
            )
        );
        $checkbox->addDecorator(new HelpText());
        $this->addElement($checkbox);
    }

    /**
     * Validate this form with the Zend validation mechanism and perform a logic validation of the connection.
     *
     * If logic validation fails, the 'backend_force_creation' checkbox is prepended to the form to allow users to
     * skip the logic connection validation.
     *
     * @param   array   $data   The form input to validate
     *
     * @return  bool            Whether validation succeeded or not
     */
    public function isValid($data)
    {
        if (!parent::isValid($data)) {
            return false;
        }
        if (isset($data['backend_force_creation']) && $data['backend_force_creation']) {
            return true;
        }
        if (!$this->isValidAuthenticationBackend()) {
            $this->addForceCreationCheckbox();
            return false;
        }
        return true;
    }

    /**
     * Return an array containing all sections defined by this form as the key and all settings
     * as an key-value sub-array
     *
     * @return  array
     */
    abstract public function getConfig();

    /**
     * Validate the configuration state of this backend with the concrete authentication backend.
     *
     * An implementation should not throw any exception, but use the add/setErrorMessages method of
     * Zend_Form. If the 'backend_force_creation' checkbox is set, this method won't be called.
     *
     * @return  bool    Whether validation succeeded or not
     */
    abstract public function isValidAuthenticationBackend();
}
