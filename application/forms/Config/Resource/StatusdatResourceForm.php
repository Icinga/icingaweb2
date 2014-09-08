<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config\Resource;

use Icinga\Web\Form;
use Icinga\Application\Icinga;
use Icinga\Web\Form\Validator\ReadablePathValidator;

/**
 * Form class for adding/modifying statusdat resources
 */
class StatusdatResourceForm extends Form
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_resource_statusdat');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'status_file',
            array(
                'required'      => true,
                'label'         => t('Filepath'),
                'description'   => t('Location of your icinga status.dat file'),
                'value'         => realpath(Icinga::app()->getApplicationDir() . '/../var/status.dat'),
                'validators'    => array(new ReadablePathValidator())
            )
        );
        $this->addElement(
            'text',
            'object_file',
            array(
                'required'      => true,
                'label'         => t('Filepath'),
                'description'   => t('Location of your icinga objects.cache file'),
                'value'         => realpath(Icinga::app()->getApplicationDir() . '/../var/objects.cache'),
                'validators'    => array(new ReadablePathValidator())
            )
        );
    }
}
