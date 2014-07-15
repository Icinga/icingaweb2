<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config\Authentication;

use \Exception;
use \Zend_Config;
use Icinga\Data\ResourceFactory;
use Icinga\Authentication\DbConnection;
use Icinga\Authentication\Backend\DbUserBackend;

/**
 * Form class for adding/modifying database authentication backends
 */
class DbBackendForm extends BaseBackendForm
{
    /**
     * Return content of the resources.ini or previously set resources
     *
     * @return  array
     */
    public function getResources()
    {
        if ($this->resources === null) {
            $res = ResourceFactory::getResourceConfigs('db')->toArray();

            foreach (array_keys($res) as $key) {
                $res[$key] = $key;
            }

            return $res;
        } else {
            return $this->resources;
        }
    }

    /**
     * Create this form and add all required elements
     *
     * @see Form::create()
     */
    public function create()
    {
        $this->setName('form_modify_backend');
        $name = $this->filterName($this->getBackendName());
        $this->addElement(
            'text',
            'backend_' . $name . '_name',
            array(
                'required'      => true,
                'allowEmpty'    => false,
                'label'         => t('Backend Name'),
                'helptext'      => t('The name of this authentication provider'),
                'value'         => $this->getBackendName()
            )
        );

        $this->addElement(
            'select',
            'backend_' . $name . '_resource',
            array(
                'required'      => true,
                'allowEmpty'    => false,
                'label'         => t('Database Connection'),
                'helptext'      => t('The database connection to use for authenticating with this provider'),
                'value'         => $this->getBackend()->get('resource'),
                'multiOptions'  => $this->getResources()
            )
        );

        $this->addElement(
            'button',
            'btn_submit',
            array(
                'type'      => 'submit',
                'value'     => '1',
                'escape'    => false,
                'class'     => 'btn btn-cta btn-wide',
                'label'     => '<i class="icinga-icon-save"></i> Save Backend'
            )
        );
    }

    /**
     * Return the datatbase authentication backend configuration for this form
     *
     * @return  array
     *
     * @see     BaseBackendForm::getConfig()
     */
    public function getConfig()
    {
        $prefix = 'backend_' . $this->filterName($this->getBackendName()) . '_';
        $section = $this->getValue($prefix . 'name');
        $cfg = array(
            'backend'   => 'db',
            'resource'  => $this->getValue($prefix . 'resource'),
        );

        return array($section => $cfg);
    }

    /**
     * Validate the current configuration by creating a backend and requesting the user count
     *
     * @return  bool    Whether validation succeeded or not
     *
     * @see BaseBackendForm::isValidAuthenticationBackend
     */
    public function isValidAuthenticationBackend()
    {
        try {
            $testConnection = ResourceFactory::createResource(ResourceFactory::getResourceConfig(
                $this->getValue('backend_' . $this->filterName($this->getBackendName()) . '_resource')
            ));
            $dbUserBackend = new DbUserBackend($testConnection);
            if ($dbUserBackend->count() < 1) {
                $this->addErrorMessage(t("No users found under the specified database backend"));
                return false;
            }
        } catch (Exception $e) {
            $this->addErrorMessage(sprintf(t('Using the specified backend failed: %s'), $e->getMessage()));
            return false;
        }
        return true;
    }
}
