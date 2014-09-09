<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config\Resource;

use Icinga\Web\Form;
use Icinga\Web\Form\Validator\ReadablePathValidator;

/**
 * Form class for adding/modifying file resources
 */
class FileResourceForm extends Form
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_resource_file');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'filename',
            array(
                'required'      => true,
                'label'         => t('Filepath'),
                'description'   => t('The filename to fetch information from'),
                'validators'    => array(new ReadablePathValidator())
            )
        );
        $this->addElement(
            'text',
            'fields',
            array(
                'required'      => true,
                'label'         => t('Pattern'),
                'description'   => t('The regular expression by which to identify columns')
            )
        );

        return $this;
    }
}
