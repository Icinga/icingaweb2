<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Forms;

use Icinga\Web\Form;
use Icinga\Forms\Config\ResourceConfigForm;
use Icinga\Forms\Config\Resource\LdapResourceForm;

/**
 * Wizard page to define the connection details for a LDAP resource
 */
class LdapResourcePage extends Form
{
    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setName('setup_ldap_resource');
        $this->setTitle($this->translate('LDAP Resource', 'setup.page.title'));
        $this->addDescription($this->translate(
            'Now please configure your AD/LDAP resource. This will later '
            . 'be used to authenticate users logging in to Icinga Web 2.'
        ));
        $this->setValidatePartial(true);
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'hidden',
            'type',
            array(
                'required'  => true,
                'value'     => 'ldap'
            )
        );

        if (isset($formData['skip_validation']) && $formData['skip_validation']) {
            $this->addSkipValidationCheckbox();
        } else {
            $this->addElement(
                'hidden',
                'skip_validation',
                array(
                    'required'  => true,
                    'value'     => 0
                )
            );
        }

        $resourceForm = new LdapResourceForm();
        $this->addElements($resourceForm->createElements($formData)->getElements());
        $this->getElement('name')->setValue('icingaweb_ldap');
    }

    /**
     * Validate the given form data and check whether a BIND-request is successful
     *
     * @param   array   $data   The data to validate
     *
     * @return  bool
     */
    public function isValid($data)
    {
        if (! parent::isValid($data)) {
            return false;
        }

        if (! isset($data['skip_validation']) || $data['skip_validation'] == 0) {
            $inspection = ResourceConfigForm::inspectResource($this);
            if ($inspection !== null && $inspection->hasError()) {
                $this->error($inspection->getError());
                $this->addSkipValidationCheckbox();
                return false;
            }
        }

        return true;
    }

    /**
     * Run the configured backend's inspection checks and show the result, if necessary
     *
     * This will only run any validation if the user pushed the 'backend_validation' button.
     *
     * @param   array   $formData
     *
     * @return  bool
     */
    public function isValidPartial(array $formData)
    {
        if (isset($formData['backend_validation']) && parent::isValid($formData)) {
            $inspection = ResourceConfigForm::inspectResource($this);
            if ($inspection !== null) {
                $join = function ($e) use (& $join) {
                    return is_string($e) ? $e : join("\n", array_map($join, $e));
                };
                $this->addElement(
                    'note',
                    'inspection_output',
                    array(
                        'order'         => 0,
                        'value'         => '<strong>' . $this->translate('Validation Log') . "</strong>\n\n"
                            . join("\n", array_map($join, $inspection->toArray())),
                        'decorators'    => array(
                            'ViewHelper',
                            array('HtmlTag', array('tag' => 'pre', 'class' => 'log-output')),
                        )
                    )
                );

                if ($inspection->hasError()) {
                    $this->warning(sprintf(
                        $this->translate('Failed to successfully validate the configuration: %s'),
                        $inspection->getError()
                    ));
                    return false;
                }
            }

            $this->info($this->translate('The configuration has been successfully validated.'));
        } elseif (! isset($formData['backend_validation'])) {
            // This is usually done by isValid(Partial), but as we're not calling any of these...
            $this->populate($formData);
        }

        return true;
    }

    /**
     * Add a checkbox to the form by which the user can skip the connection validation
     */
    protected function addSkipValidationCheckbox()
    {
        $this->addElement(
            'checkbox',
            'skip_validation',
            array(
                'required'      => true,
                'label'         => $this->translate('Skip Validation'),
                'description'   => $this->translate(
                    'Check this to not to validate connectivity with the given directory service'
                )
            )
        );
    }
}
