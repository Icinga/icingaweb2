<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config\Authentication;

use Icinga\Web\Form;

/**
 * Base form for authentication backend forms
 */
abstract class BaseBackendForm extends Form
{
    /**
     * @see Form::addSubmitButton()
     */
    public function addSubmitButton()
    {
        $this->addElement(
            'submit',
            'btn_submit',
            array(
                'label' => t('Save Changes')
            )
        );

        return $this;
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
     * Validate the configuration state of this backend with the concrete authentication backend.
     *
     * An implementation should not throw any exception, but use the add/setErrorMessages method of
     * Zend_Form. If the 'backend_force_creation' checkbox is set, this method won't be called.
     *
     * @return  bool    Whether validation succeeded or not
     */
    abstract public function isValidAuthenticationBackend();

    /**
     * Return the backend's configuration values and its name
     *
     * The first value is the name and the second one the values as array.
     *
     * @return  array
     */
    public function getBackendConfig()
    {
        $values = $this->getValues();
        $name = $values['name'];
        unset($values['name']);
        unset($values['btn_submit']);
        unset($values['force_creation']);
        unset($values[$this->getTokenElementName()]);
        return array($name, $values);
    }

    /**
     * Populate the form with the given configuration values
     *
     * @param   string  $name       The name of the backend
     * @param   array   $config     The configuration values
     */
    public function setBackendConfig($name, array $config)
    {
        $config['name'] = $name;
        $this->populate($config);
    }

    /**
     * Add a checkbox to be displayed at the beginning of the form
     * which allows the user to skip the connection validation
     */
    protected function addForceCreationCheckbox()
    {
        $this->addElement(
            'checkbox',
            'force_creation',
            array(
                'order'     => 0,
                'label'     => t('Force Changes'),
                'helptext'  => t('Check this box to enforce changes without connectivity validation')
            )
        );
    }
}
