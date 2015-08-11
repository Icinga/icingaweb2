<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\Resource;

use Zend_Validate_Callback;
use Icinga\Web\Form;

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
                'validators'    => array('ReadablePathValidator')
            )
        );
        $callbackValidator = new Zend_Validate_Callback(function ($value) {
            return @preg_match($value, '') !== false;
        });
        $callbackValidator->setMessage(
            $this->translate('"%value%" is not a valid regular expression.'),
            Zend_Validate_Callback::INVALID_VALUE
        );
        $this->addElement(
            'text',
            'fields',
            array(
                'required'      => true,
                'label'         => $this->translate('Pattern'),
                'description'   => $this->translate('The pattern by which to identify columns.'),
                'requirement'   => $this->translate('The column pattern must be a valid regular expression.'),
                'validators'    => array($callbackValidator)
            )
        );

        return $this;
    }
}
