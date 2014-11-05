<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config\Authentication;

use Exception;
use Icinga\Web\Form;
use Icinga\Web\Request;
use Icinga\Data\ResourceFactory;
use Icinga\Authentication\Backend\DbUserBackend;

/**
 * Form class for adding/modifying database authentication backends
 */
class DbBackendForm extends Form
{
    /**
     * The database resource names the user can choose from
     *
     * @var array
     */
    protected $resources;

    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_authbackend_db');
    }

    /**
     * Set the resource names the user can choose from
     *
     * @param   array   $resources      The resources to choose from
     *
     * @return  self
     */
    public function setResources(array $resources)
    {
        $this->resources = $resources;
        return $this;
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'name',
            array(
                'required'      => true,
                'label'         => t('Backend Name'),
                'description'   => t('The name of this authentication provider'),
            )
        );
        $this->addElement(
            'select',
            'resource',
            array(
                'required'      => true,
                'label'         => t('Database Connection'),
                'description'   => t('The database connection to use for authenticating with this provider'),
                'multiOptions'  => false === empty($this->resources)
                    ? array_combine($this->resources, $this->resources)
                    : array()
            )
        );
        $this->addElement(
            'hidden',
            'backend',
            array(
                'required'  => true,
                'value'     => 'db'
            )
        );

        return $this;
    }

    /**
     * Validate that the selected resource is a valid database authentication backend
     *
     * @see Form::onSuccess()
     */
    public function onSuccess(Request $request)
    {
        if (false === $this->isValidAuthenticationBackend($this)) {
            return false;
        }
    }

    /**
     * Validate the configuration by creating a backend and requesting the user count
     *
     * @param   Form    $form   The form to fetch the configuration values from
     *
     * @return  bool            Whether validation succeeded or not
     */
    public function isValidAuthenticationBackend(Form $form)
    {
        $element = $form->getElement('resource');

        try {
            $dbUserBackend = new DbUserBackend(ResourceFactory::create($element->getValue()));
            if ($dbUserBackend->count() < 1) {
                $element->addError(t('No users found under the specified database backend'));
                return false;
            }
        } catch (Exception $e) {
            $element->addError(sprintf(t('Using the specified backend failed: %s'), $e->getMessage()));
            return false;
        }

        return true;
    }
}
