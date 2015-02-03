<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Forms\Config\Resource;

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
            'name',
            array(
                'required'      => true,
                'label'         => $this->translate('Resource Name'),
                'description'   => $this->translate('The unique name of this resource')
            )
        );
        $this->addElement(
            'text',
            'filename',
            array(
                'required'      => true,
                'label'         => $this->translate('Filepath'),
                'description'   => $this->translate('The filename to fetch information from'),
                'validators'    => array(new ReadablePathValidator())
            )
        );
        $this->addElement(
            'text',
            'fields',
            array(
                'required'      => true,
                'label'         => $this->translate('Pattern'),
                'description'   => $this->translate('The regular expression by which to identify columns')
            )
        );

        return $this;
    }
}
