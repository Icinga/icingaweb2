<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config\Authentication;

use \Exception;
use \Zend_Config;
use Icinga\Web\Form;
use Icinga\Data\ResourceFactory;
use Icinga\Authentication\Backend\LdapUserBackend;

/**
 * Form for adding or modifying LDAP authentication backends
 */
class LdapBackendForm extends BaseBackendForm
{
    /**
     * Return content of the resources.ini or previously set resources
     *
     * @return  array
     */
    public function getResources()
    {
        if ($this->resources === null) {
            $res = ResourceFactory::getResourceConfigs('ldap')->toArray();

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
        $backend = $this->getBackend();

        $this->addElement(
            'text',
            'backend_' . $name . '_name',
            array(
                'required'      => true,
                'allowEmpty'    => false,
                'label'         => t('Backend Name'),
                'helptext'      => t('The name of this authentication backend'),
                'value'         => $this->getBackendName()
            )
        );

        $this->addElement(
            'select',
            'backend_' . $name . '_resource',
            array(
                'required'      => true,
                'allowEmpty'    => false,
                'label'         => t('LDAP Resource'),
                'helptext'      => t('The resource to use for authenticating with this provider'),
                'value'         => $this->getBackend()->get('resource'),
                'multiOptions'  => $this->getResources()
            )
        );

        $this->addElement(
            'text',
            'backend_' . $name . '_user_class',
            array(
                'required'  => true,
                'label'     => t('LDAP User Object Class'),
                'helptext'  => t('The object class used for storing users on the ldap server'),
                'value'     => $backend->get('user_class', 'inetOrgPerson')
            )
        );

        $this->addElement(
            'text',
            'backend_' . $name . '_user_name_attribute',
            array(
                'required'  => true,
                'label'     => t('LDAP User Name Attribute'),
                'helptext'  => t('The attribute name used for storing the user name on the ldap server'),
                'value'     => $backend->get('user_name_attribute', 'uid')
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
     * Return the ldap authentication backend configuration for this form
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
            'backend'               => 'ldap',
            'resource'              => $this->getValue($prefix . 'resource'),
            'user_class'            => $this->getValue($prefix . 'user_class'),
            'user_name_attribute'   => $this->getValue($prefix . 'user_name_attribute')
        );
        return array($section => $cfg);
    }

    /**
     * Validate the current configuration by creating a backend and requesting the user count
     *
     * @return  bool    Whether validation succeeded or not
     *
     * @see BaseBackendForm::isValidAuthenticationBacken
     */
    public function isValidAuthenticationBackend()
    {
        if (! ResourceFactory::ldapAvailable()) {
            /*
             * It should be possible to run icingaweb without the php ldap extension, when
             * no ldap backends are needed. When the user tries to create an ldap backend
             * without ldap installed we need to show him an error.
             */
            $this->addErrorMessage(t('Using ldap is not possible, the php extension "ldap" is not installed.'));
            return false;
        }
        try {
            $cfg = $this->getConfig();
            $backendName = 'backend_' . $this->filterName($this->getBackendName()) . '_name';
            $backendConfig = new Zend_Config($cfg[$this->getValue($backendName)]);
            $backend = ResourceFactory::createResource(ResourceFactory::getResourceConfig($backendConfig->resource));
            $testConn = new LdapUserBackend(
                $backend,
                $backendConfig->user_class,
                $backendConfig->user_name_attribute
            );
            $testConn->assertAuthenticationPossible();
            /*
            if ($testConn->count() === 0) {
                throw new Exception('No Users Found On Directory Server');
            }
            */
        } catch (Exception $exc) {
            $this->addErrorMessage(
                t('Connection Validation Failed: ' . $exc->getMessage())
            );
            return false;
        }

        return true;
    }
}
