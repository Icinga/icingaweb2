<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Eventdb\Forms\Config;

use Exception;
use Icinga\Data\ResourceFactory;
use Icinga\Forms\ConfigForm;

/**
 * Form for managing the connection to the EventDB backend
 */
class BackendConfigForm extends ConfigForm
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setSubmitLabel($this->translate('Save'));
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        $resources = ResourceFactory::getResourceConfigs('db')->keys();

        $this->addElement(
            'select',
            'backend_resource',
            array(
                'description'   => $this->translate('The resource to use'),
                'label'         => $this->translate('Resource'),
                'multiOptions'  => array_combine($resources, $resources),
                'required'      => true
            )
        );

        if (isset($formData['skip_validation']) && $formData['skip_validation']) {
            $this->addSkipValidationCheckbox();
        }
    }

    /**
     * Return whether the given values are valid
     *
     * @param   array   $formData   The data to validate
     *
     * @return  bool
     */
    public function isValid($formData)
    {
        if (! parent::isValid($formData)) {
            return false;
        }
        
        if (($el = $this->getElement('skip_validation')) === null || ! $el->isChecked()) {
            $resourceConfig = ResourceFactory::getResourceConfig($this->getValue('backend_resource'));

            if (! $this->isValidEventDbSchema($resourceConfig)) {
                if ($el === null) {
                    $this->addSkipValidationCheckbox();
                }

                return false;
            }
        }

        return true;
    }

    public function isValidEventDbSchema($resourceConfig)
    {
        try {
            $db = ResourceFactory::createResource($resourceConfig);
            $db->select()->from('event', array('id'))->fetchOne();
        } catch (Exception $_) {
            $this->error($this->translate(
                'Cannot find the EventDB schema. Please verify that the given database '
                . 'contains the schema and that the configured user has access to it.'
            ));
            return false;
        }
        return true;
    }

    /**
     * Add a checkbox to the form by which the user can skip the schema validation
     */
    protected function addSkipValidationCheckbox()
    {
        $this->addElement(
            'checkbox',
            'skip_validation',
            array(
                'description'   => $this->translate(
                    'Check this to not to validate the EventDB schema of the chosen resource'
                ),
                'ignore'        => true,
                'label'         => $this->translate('Skip Validation'),
                'order'         => 0
            )
        );
    }
}
