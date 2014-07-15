<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Config\Backend;

use \Zend_Config;

/**
 * Extended EditBackendForm for creating new Backends
 *
 * @see EditBackendForm
 */
class CreateBackendForm extends EditBackendForm
{
    /**
     * Create this form
     *
     * @see EditBackendForm::create()
     */
    public function create()
    {
        $this->setBackendConfiguration(new Zend_Config(array('type' => 'ido')));
        $this->addElement(
            'text',
            'backend_name',
            array(
                'label'     =>  'Backend Name',
                'required'  =>  true,
                'helptext'  =>  'This will be the identifier of this backend'
            )
        );
        parent::create();
    }

    /**
     * Return the name of the backend that is to be created
     *
     * @return string The name of the backend as entered in the form
     */
    public function getBackendName()
    {
        return $this->getValue('backend_name');
    }
}
